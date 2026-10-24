<?php
declare(strict_types=1);

namespace App;

/**
 * High-level orchestration for БОРИКА card payments.
 * Borica (low-level) signs/verifies; Payment owns the pending→paid/failed
 * transition and tells the caller when to send the success emails.
 */
final class Payment
{
    /**
     * Creates a payments row for the order and returns the signed redirect form.
     *
     * @return array{url:string, fields:array<string,string>}
     */
    public static function startForOrder(array $order, string $backref): array
    {
        Db::execute(
            'INSERT INTO payments (order_id, provider, trtype, amount, currency, sent_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [(int) $order['id'], 'borica', 1, (float) $order['total'], (string) $order['currency'], date('Y-m-d H:i:s')]
        );
        $paymentId = Db::lastInsertId();
        $orderRef  = str_pad((string) $paymentId, 6, '0', STR_PAD_LEFT);

        $fields = Borica::buildSaleFields($order, $orderRef, $backref);

        Db::execute(
            'UPDATE payments SET order_ref = ?, nonce = ? WHERE id = ?',
            [$orderRef, $fields['NONCE'], $paymentId]
        );

        return ['url' => Borica::endpointUrl(), 'fields' => $fields];
    }

    /**
     * Handles БОРИКА's response posted to the Return URL.
     * Idempotent: a repeated POST will not re-trigger emails or downgrade a paid order.
     *
     * @return array{order:?array, success:bool, first_success:bool}
     */
    public static function handleReturn(array $post): array
    {
        $orderRef = (string) ($post['ORDER'] ?? '');
        $payment  = Db::fetchOne('SELECT * FROM payments WHERE order_ref = ? ORDER BY id DESC LIMIT 1', [$orderRef]);
        if (!$payment) {
            return ['order' => null, 'success' => false, 'first_success' => false];
        }

        $valid = false;
        try {
            $valid = Borica::verifyResponse($post);
        } catch (\Throwable $e) {
            error_log('БОРИКА verify failed: ' . $e->getMessage());
            $valid = false;
        }

        $action  = (string) ($post['ACTION'] ?? '');
        $rc      = (string) ($post['RC'] ?? '');
        $success = $valid && $action === '0' && $rc === '00';

        $alreadyPaid = ($payment['action'] === '0' && (int) $payment['signature_valid'] === 1);

        Db::execute(
            'UPDATE payments
                SET action = ?, rc = ?, status_msg = ?, approval = ?, rrn = ?, int_ref = ?,
                    signature_valid = ?, raw_response = ?
              WHERE id = ?',
            [
                $action,
                $rc,
                (string) ($post['STATUSMSG'] ?? ''),
                (string) ($post['APPROVAL'] ?? ''),
                (string) ($post['RRN'] ?? ''),
                (string) ($post['INT_REF'] ?? ''),
                $valid ? 1 : 0,
                json_encode($post, JSON_UNESCAPED_UNICODE),
                (int) $payment['id'],
            ]
        );

        $order = Order::find((int) $payment['order_id']);
        if (!$order) {
            return ['order' => null, 'success' => false, 'first_success' => false];
        }

        // Only move forward on the finalizing transition; never downgrade an already-paid order.
        if (!$alreadyPaid) {
            $newPayStatus = $success ? 'paid' : 'failed';
            if ($order['payment_status'] !== $newPayStatus) {
                Db::execute('UPDATE orders SET payment_status = ? WHERE id = ?', [$newPayStatus, (int) $order['id']]);
                $order['payment_status'] = $newPayStatus;
            }
            // Reflect the outcome in the visible workflow status (card orders).
            $workflowStatus = $success ? 'paid' : 'payment_failed';
            if ($order['status'] !== $workflowStatus) {
                $order = Order::changeStatus(
                    (int) $order['id'],
                    $workflowStatus,
                    $success ? 'Платено по карта (БОРИКА)' : 'Неуспешно картово плащане (БОРИКА)',
                    null
                );
            }
        }

        return [
            'order'         => $order,
            'success'       => $success || $alreadyPaid,
            'first_success' => $success && !$alreadyPaid,
        ];
    }
}
