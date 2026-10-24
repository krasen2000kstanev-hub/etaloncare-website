<?php
declare(strict_types=1);

namespace App;

/**
 * НАП стандартизиран одиторски файл (Приложение 38 / Наредба Н-18).
 * Схемата е закотната в app/nap/schema.xsd. Изход: windows-1251.
 */
final class NapAudit
{
    private const SCHEMA = __DIR__ . '/nap/schema.xsd';

    private static function fmt(float $n): string
    {
        return number_format(round($n, 2), 2, '.', '');
    }

    /**
     * @param array $header ['eik','e_shop_n','domain_name','e_shop_type','creation_date','mon','god']
     * @param array $orders всеки: ['ord_n','ord_d','doc_n','doc_date','paym','pos_n','trans_n','proc_id',
     *                            'items'=>[['name','qty','unit_price'], ...]]
     * @param int   $vatRate ДДС ставка (напр. 9); цената е С включено ДДС.
     */
    public static function buildDom(array $header, array $orders, int $vatRate): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $audit = $dom->createElement('audit');
        $dom->appendChild($audit);

        $add = function (\DOMElement $parent, string $name, string $value) use ($dom): \DOMElement {
            $el = $dom->createElement($name);
            $el->appendChild($dom->createTextNode($value));
            $parent->appendChild($el);
            return $el;
        };

        $add($audit, 'eik', (string) $header['eik']);
        $add($audit, 'e_shop_n', (string) $header['e_shop_n']);
        $add($audit, 'domain_name', (string) $header['domain_name']);
        $add($audit, 'e_shop_type', (string) $header['e_shop_type']);
        $add($audit, 'creation_date', (string) $header['creation_date']);
        $add($audit, 'mon', (string) $header['mon']);
        $add($audit, 'god', (string) $header['god']);

        $orderWrap = $dom->createElement('order');
        $audit->appendChild($orderWrap);

        $rate = $vatRate / 100.0;

        foreach ($orders as $o) {
            $oe = $dom->createElement('orderenum');
            $orderWrap->appendChild($oe);
            $add($oe, 'ord_n', (string) $o['ord_n']);
            $add($oe, 'ord_d', (string) $o['ord_d']);
            $add($oe, 'doc_n', (string) $o['doc_n']);
            $add($oe, 'doc_date', (string) $o['doc_date']);

            $artWrap = $dom->createElement('art');
            $oe->appendChild($artWrap);

            $sumWith = 0.0; $sumBase = 0.0;
            foreach ($o['items'] as $it) {
                $qty   = (float) $it['qty'];
                $gross = (float) $it['unit_price'];          // ед. цена С ДДС
                $artSum   = round($gross * $qty, 2);          // ред С ДДС
                $unitBase = round($gross / (1 + $rate), 2);   // ед. цена БЕЗ ДДС
                $lineBase = round($artSum / (1 + $rate), 2);
                $artVat   = round($artSum - $lineBase, 2);
                $sumWith += $artSum; $sumBase += $lineBase;

                $ae = $dom->createElement('artenum');
                $artWrap->appendChild($ae);
                $add($ae, 'art_name', (string) $it['name']);
                $add($ae, 'art_quant', self::fmt($qty));
                $add($ae, 'art_price', self::fmt($unitBase));
                $add($ae, 'art_vat_rate', (string) $vatRate);
                $add($ae, 'art_vat', self::fmt($artVat));
                $add($ae, 'art_sum', self::fmt($artSum));
            }

            $add($oe, 'ord_total1', self::fmt($sumBase));
            $add($oe, 'ord_disc', '0.00');
            $add($oe, 'ord_vat', self::fmt($sumWith - $sumBase));
            $add($oe, 'ord_total2', self::fmt($sumWith));
            $add($oe, 'paym', (string) $o['paym']);
            if (!empty($o['pos_n']))   $add($oe, 'pos_n', (string) $o['pos_n']);
            if (!empty($o['trans_n'])) $add($oe, 'trans_n', (string) $o['trans_n']);
            if (!empty($o['proc_id'])) $add($oe, 'proc_id', (string) $o['proc_id']);
        }

        $add($audit, 'r_ord', '0'); // връщания не се проследяват в системата (v1)

        return $dom;
    }

    /** Връща готовия файл в windows-1251. */
    public static function build(array $header, array $orders, int $vatRate): string
    {
        $dom = self::buildDom($header, $orders, $vatRate);
        $utf8 = (string) $dom->saveXML();
        $cp1251 = (string) iconv('UTF-8', 'WINDOWS-1251//TRANSLIT', $utf8);
        return preg_replace('/encoding="UTF-8"/i', 'encoding="windows-1251"', $cp1251, 1);
    }

    /**
     * Валидира DOM срещу закотнатата XSD. Връща списък с грешки (празен = валиден).
     * @return string[]
     */
    public static function validate(\DOMDocument $dom): array
    {
        $prev = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $ok = $dom->schemaValidate(self::SCHEMA);
        $errors = [];
        if (!$ok) {
            foreach (libxml_get_errors() as $e) {
                $errors[] = trim($e->message) . ' (ред ' . $e->line . ')';
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $errors;
    }

    /**
     * Събира хедъра (от settings + период) и поръчките, доставени в [from,to].
     * $pay ∈ {'card','cod','all'}. Връща ['header'=>..., 'orders'=>..., 'vat'=>int].
     */
    public static function collect(string $from, string $to, string $pay): array
    {
        $s = Settings::getMany([
            'nap_company_eik', 'nap_shop_uid', 'nap_shop_domain', 'nap_shop_type',
            'nap_pos_terminal', 'nap_pos_provider_id', 'nap_vat_rate',
        ]);
        $header = [
            'eik'           => $s['nap_company_eik'] ?? '',
            'e_shop_n'      => $s['nap_shop_uid'] ?? '',
            'domain_name'   => $s['nap_shop_domain'] ?? '',
            'e_shop_type'   => $s['nap_shop_type'] ?? '1',
            'creation_date' => gmdate('Y-m-d'),
            'mon'           => date('m', (int) strtotime($from)),
            'god'           => (int) date('Y', (int) strtotime($from)),
        ];

        // Distinct order ids delivered in the period (avoids GROUP BY o.* which breaks
        // under ONLY_FULL_GROUP_BY on some MySQL configs), then load each order.
        $sql = 'SELECT DISTINCT o.id
                  FROM orders o
                  JOIN order_status_log l ON l.order_id = o.id AND l.to_status = \'delivered\'
                 WHERE DATE(l.created_at) BETWEEN ? AND ?';
        $params = [$from, $to];
        if ($pay === 'card' || $pay === 'cod') {
            $sql .= ' AND o.payment_method = ?';
            $params[] = $pay;
        }
        $sql .= ' ORDER BY o.id';
        $ids = Db::fetchAll($sql, $params);

        $orders = [];
        foreach ($ids as $row) {
            $o = Order::find((int) $row['id']);
            if (!$o) continue;
            $isCard = $o['payment_method'] === 'card';
            $rrn = ''; $procId = '';
            if ($isCard) {
                $p = Db::fetchOne(
                    'SELECT rrn FROM payments WHERE order_id = ? AND action = \'0\' AND signature_valid = 1 ORDER BY id DESC LIMIT 1',
                    [(int) $o['id']]
                );
                $rrn = $p['rrn'] ?? '';
                $procId = $s['nap_pos_provider_id'] ?? '';
            }
            $items = [];
            foreach (Order::items((int) $o['id']) as $it) {
                $items[] = ['name' => $it['name'], 'qty' => (float) $it['qty'], 'unit_price' => (float) $it['unit_price']];
            }
            $orders[] = [
                'ord_n'    => $o['order_number'],
                'ord_d'    => date('Y-m-d', (int) strtotime($o['created_at'])),
                'doc_n'    => (string) $o['id'],
                'doc_date' => date('Y-m-d', (int) strtotime($o['created_at'])),
                'paym'     => $isCard ? 2 : 3,
                'pos_n'    => $isCard ? ($s['nap_pos_terminal'] ?? '') : '',
                'trans_n'  => $rrn,
                'proc_id'  => $procId,
                'items'    => $items,
            ];
        }

        return ['header' => $header, 'orders' => $orders, 'vat' => (int) ($s['nap_vat_rate'] ?? 9)];
    }
}
