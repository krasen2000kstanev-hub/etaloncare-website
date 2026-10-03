import { DatabaseSync } from 'node:sqlite';
export class SqliteStore {
  constructor(file = ':memory:') {
    this.db = new DatabaseSync(file);
    this.db.exec('PRAGMA busy_timeout = 5000; CREATE TABLE IF NOT EXISTS orders (id TEXT PRIMARY KEY, bank TEXT UNIQUE, data TEXT NOT NULL); CREATE TABLE IF NOT EXISTS seats (id TEXT PRIMARY KEY, owner TEXT NOT NULL); CREATE TABLE IF NOT EXISTS limits (id TEXT PRIMARY KEY, count INTEGER NOT NULL);');
  }
  get(id) { const row = this.db.prepare('SELECT data FROM orders WHERE id=?').get(id); return row ? JSON.parse(row.data) : null; }
  byBank(bank) { const row = this.db.prepare('SELECT data FROM orders WHERE bank=?').get(bank); return row ? JSON.parse(row.data) : null; }
  async occupied() { return this.db.prepare('SELECT id FROM seats').all().map(r => r.id); }
  async pending() { return this.db.prepare('SELECT data FROM orders').all().map(r => JSON.parse(r.data)).filter(o => o.status === 'pending'); }
  async limit(source, minute, maximum) {
    this.db.prepare('DELETE FROM limits WHERE CAST(substr(id,1,instr(id,\':\')-1) AS INTEGER) < ?').run(minute - 5);
    const row = this.db.prepare('INSERT INTO limits(id,count) VALUES(?,1) ON CONFLICT(id) DO UPDATE SET count=count+1 RETURNING count').get(`${minute}:${source}`);
    return row.count <= maximum;
  }
  async reserve(order) {
    this.db.exec('BEGIN IMMEDIATE');
    try {
      if (this.byBank(order.bankOrder)) { this.db.exec('ROLLBACK'); return 'bankCollision'; }
      this.db.prepare('INSERT INTO orders VALUES(?,?,?)').run(order.id, order.bankOrder, JSON.stringify(order));
      this.db.prepare('INSERT INTO seats VALUES(?,?)').run(order.seatId, order.id);
      this.db.exec('COMMIT'); return 'ok';
    } catch (error) { this.db.exec('ROLLBACK'); if (String(error.message).includes('UNIQUE')) return 'conflict'; throw error; }
  }
  async finalize(order, result) {
    this.db.exec('BEGIN IMMEDIATE');
    try {
      const current = this.get(order.id);
      if (current.status === 'pending') {
        this.db.prepare('UPDATE orders SET data=? WHERE id=?').run(JSON.stringify({ ...current, ...result }), order.id);
        if (result.status === 'failed') this.db.prepare('DELETE FROM seats WHERE id=? AND owner=?').run(order.seatId, order.id);
      }
      this.db.exec('COMMIT');
    } catch (error) { this.db.exec('ROLLBACK'); throw error; }
  }
  close() { this.db.close(); }
}
