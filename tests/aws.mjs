import assert from 'node:assert/strict';
import { readFile, writeFile } from 'node:fs/promises';
import { randomUUID, randomBytes, createHash } from 'node:crypto';
import { DynamoStore } from '../backend/dynamo.mjs';
import { Service, seatPlan } from '../backend/service.mjs';
import { DeleteCommand } from '@aws-sdk/lib-dynamodb';
const deployment = JSON.parse(await readFile('deployment.local.json', 'utf8'));
const config = JSON.parse(await readFile('backend/event.json', 'utf8'));
assert.equal(config.registrationEnabled, false, 'Do not run this mutating test on an open event');
const url = deployment.ApiUrl.replace(/\/$/, '');
const availability = await (await fetch(url + '/api/seats')).json();
assert.equal(availability.registrationOpen, false); assert.equal(availability.seats.filter(s => !s.available).length, 0, 'Preview table must be empty');
process.env.AWS_REGION = deployment.region; process.env.AWS_PROFILE = deployment.profile;
const store = new DynamoStore(deployment.TableName); store.seatIds = seatPlan(config).map(s => s.id);
const payment = { ready: true, config: { terminal: 'TESTONLY' }, checkout: () => ({ url: 'https://example.invalid', fields: {} }) };
const clock = () => Date.parse('2026-11-02T12:00:00Z');
const service = new Service({ store, payment, config: { ...config, registrationEnabled: true, seatPlanApproved: true, termsApproved: true }, clock });
const requests = Array.from({ length: 5 }, () => ({ seatId: 'C10', name: 'TEST ONLY', email: 'test@example.com', phone: '+359888000000', cardholderName: 'TEST ONLY', billingAddress: 'Test fixture, not real address', consent: true, requestId: randomUUID(), token: randomBytes(32).toString('hex') }));
const source = 'qa-' + randomUUID();
const created = [];
try {
  const results = await Promise.allSettled(requests.map(r => service.create(r, source)));
  assert.equal(results.filter(r => r.status === 'fulfilled').length, 1);
  for (const request of requests) { const order = await store.get(request.requestId); if (order) created.push(order); }
  assert.equal(created.length, 1); assert.deepEqual(await store.occupied(), ['C10']);
  await store.finalize(created[0], { status: 'paid', paymentReference: 'TEST', gatewayReference: 'TEST', finalizedAt: Math.floor(clock() / 1000) });
  assert.equal((await store.get(created[0].id)).status, 'paid');
  console.log('AWS DynamoDB transaction: five concurrent requests, exactly one order/seat/bank mapping; finalization passed.');
} finally {
  for (const order of created) {
    await store.client.send(new DeleteCommand({ TableName: store.table, Key: { pk: `SEAT#${order.seatId}` }, ConditionExpression: '#owner=:owner', ExpressionAttributeNames: { '#owner': 'owner' }, ExpressionAttributeValues: { ':owner': order.id } }));
    await store.client.send(new DeleteCommand({ TableName: store.table, Key: { pk: `BANK#${order.bankOrder}` }, ConditionExpression: '#owner=:owner', ExpressionAttributeNames: { '#owner': 'owner' }, ExpressionAttributeValues: { ':owner': order.id } }));
    await store.client.send(new DeleteCommand({ TableName: store.table, Key: { pk: `ORDER#${order.id}` }, ConditionExpression: 'id=:id', ExpressionAttributeValues: { ':id': order.id } }));
  }
  await store.client.send(new DeleteCommand({ TableName: store.table, Key: { pk: `LIMIT#${Math.floor(clock() / 60000)}#${createHash('sha256').update(source).digest('hex')}` } }));
}
assert.deepEqual(await store.occupied(), []);
const closed = await fetch(url + '/api/orders', { method: 'POST', headers: { Origin: 'https://etaloncare.com', 'Content-Type': 'application/json' }, body: JSON.stringify(requests[0]) }); assert.equal(closed.status, 403);
await writeFile('output/qa/aws-report.json', JSON.stringify({ concurrentRequests: 5, successfulReservations: 1, finalized: true, testRecordsRemoved: true, publicRegistrationBlocked: true }, null, 2));
console.log('Synthetic test records removed; public registration correctly returns 403.');
