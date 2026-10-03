import { DynamoDBClient } from '@aws-sdk/client-dynamodb';
import { DynamoDBDocumentClient, GetCommand, QueryCommand, UpdateCommand, TransactWriteCommand } from '@aws-sdk/lib-dynamodb';
export class DynamoStore {
  constructor(table) { this.table = table; this.client = DynamoDBDocumentClient.from(new DynamoDBClient({}), { marshallOptions: { removeUndefinedValues: true } }); }
  async get(id) { return (await this.client.send(new GetCommand({ TableName: this.table, Key: { pk: `ORDER#${id}` }, ConsistentRead: true }))).Item || null; }
  async byBank(bank) {
    const link = (await this.client.send(new GetCommand({ TableName: this.table, Key: { pk: `BANK#${bank}` }, ConsistentRead: true }))).Item;
    return link ? this.get(link.owner) : null;
  }
  async query(group) {
    const result = await this.client.send(new QueryCommand({ TableName: this.table, IndexName: 'group-index', KeyConditionExpression: '#g = :g', ExpressionAttributeNames: { '#g': 'group' }, ExpressionAttributeValues: { ':g': group } }));
    return result.Items || [];
  }
  // Public seat reads use the base partition and a strong read, not an eventually consistent index.
  async occupied() {
    const { BatchGetCommand } = await import('@aws-sdk/lib-dynamodb');
    let remaining = this.seatIds.map(id => ({ pk: `SEAT#${id}` })), occupied = [];
    for (let attempt = 0; remaining.length && attempt < 3; attempt++) {
      const result = await this.client.send(new BatchGetCommand({ RequestItems: { [this.table]: { Keys: remaining, ConsistentRead: true } } }));
      occupied.push(...(result.Responses?.[this.table] || []).map(item => item.seatId));
      remaining = result.UnprocessedKeys?.[this.table]?.Keys || [];
    }
    if (remaining.length) throw new Error('Seat availability unavailable');
    return occupied;
  }
  async pending() { return this.query('pending'); }
  async limit(source, minute, maximum) {
    try {
      await this.client.send(new UpdateCommand({ TableName: this.table, Key: { pk: `LIMIT#${minute}#${source}` }, UpdateExpression: 'SET expiresAt = :ttl ADD hits :one', ConditionExpression: 'attribute_not_exists(hits) OR hits < :max', ExpressionAttributeValues: { ':ttl': (minute + 5) * 60, ':one': 1, ':max': maximum } }));
      return true;
    } catch (e) { if (e.name === 'ConditionalCheckFailedException') return false; throw e; }
  }
  async reserve(order) {
    const put = item => ({ Put: { TableName: this.table, Item: item, ConditionExpression: 'attribute_not_exists(pk)' } });
    try {
      await this.client.send(new TransactWriteCommand({ TransactItems: [
        put({ pk: `ORDER#${order.id}`, group: 'pending', ...order }),
        put({ pk: `SEAT#${order.seatId}`, seatId: order.seatId, owner: order.id }),
        put({ pk: `BANK#${order.bankOrder}`, owner: order.id })
      ] })); return 'ok';
    } catch (e) {
      if (e.name !== 'TransactionCanceledException') throw e;
      if (e.CancellationReasons?.[2]?.Code === 'ConditionalCheckFailed') return 'bankCollision';
      if (e.CancellationReasons?.some(r => r.Code === 'ConditionalCheckFailed')) return 'conflict';
      throw e;
    }
  }
  async finalize(order, result) {
    const update = { Update: { TableName: this.table, Key: { pk: `ORDER#${order.id}` }, UpdateExpression: 'SET #status=:s, #group=:s, paymentReference=:p, gatewayReference=:g, finalizedAt=:f', ConditionExpression: '#status=:pending', ExpressionAttributeNames: { '#status': 'status', '#group': 'group' }, ExpressionAttributeValues: { ':s': result.status, ':pending': 'pending', ':p': result.paymentReference, ':g': result.gatewayReference, ':f': result.finalizedAt } } };
    const check = { TableName: this.table, Key: { pk: `SEAT#${order.seatId}` }, ConditionExpression: '#owner=:o', ExpressionAttributeNames: { '#owner': 'owner' }, ExpressionAttributeValues: { ':o': order.id } };
    try {
      await this.client.send(new TransactWriteCommand({ TransactItems: [update, result.status === 'failed' ? { Delete: check } : { ConditionCheck: check }] }));
    } catch (e) {
      if (e.name === 'TransactionCanceledException' && (await this.get(order.id))?.status !== 'pending') return;
      throw e;
    }
  }
}
