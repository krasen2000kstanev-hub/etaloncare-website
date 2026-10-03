import { createHash, randomBytes, randomInt, randomUUID, timingSafeEqual } from 'node:crypto';
export class ApiError extends Error { constructor(status, message) { super(message); this.status = status; } }
const digest = value => createHash('sha256').update(value).digest('hex');
const safeEqual = (a, b) => typeof a === 'string' && typeof b === 'string' && a.length === b.length && timingSafeEqual(Buffer.from(a), Buffer.from(b));
export function seatPlan(config) {
  const seats = config.rows.flatMap(row => Array.from({ length: row.count }, (_, i) => ({ id: `${row.label}${String(i + 1).padStart(2, '0')}`, row: row.label, number: i + 1, tier: row.tier })));
  if (seats.length !== 100 || new Set(seats.map(s => s.id)).size !== 100 || seats.filter(s => s.tier === 'premium').length !== 20 || seats.some(s => !['standard', 'premium'].includes(s.tier))) throw new Error('Seat plan requires 100 unique seats, including 20 premium');
  return seats;
}
export class Service {
  constructor({ store, payment, config, clock = () => Date.now() }) { this.store = store; this.payment = payment; this.config = config; this.clock = clock; this.seats = seatPlan(config); }
  open() { const now = this.clock(); return this.config.registrationEnabled && this.config.seatPlanApproved && this.config.termsApproved && this.payment.ready && now >= Date.parse(this.config.registrationOpens) && now < Date.parse(this.config.registrationCloses); }
  async availability() {
    const occupied = new Set(await this.store.occupied());
    return { seats: this.seats.map(s => ({ ...s, available: !occupied.has(s.id) })), registrationOpen: Boolean(this.open()), planApproved: this.config.seatPlanApproved, planDescription: this.config.planDescription };
  }
  validate(input) {
    if (!input || typeof input !== 'object' || Array.isArray(input)) throw new ApiError(400, 'Невалидна заявка.');
    const required = ['seatId', 'name', 'email', 'phone', 'cardholderName', 'billingAddress', 'requestId', 'token'];
    if (required.some(key => typeof input[key] !== 'string')) throw new ApiError(400, 'Попълнете всички задължителни полета.');
    const clean = Object.fromEntries(required.map(key => [key, input[key].trim()]));
    if (!/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(clean.requestId) || !/^[0-9a-f]{64}$/i.test(clean.token)) throw new ApiError(400, 'Невалиден идентификатор.');
    if (clean.name.length < 3 || clean.name.length > 100 || clean.email.length > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clean.email) || !/^[+\d][\d ()-]{6,24}$/.test(clean.phone)) throw new ApiError(400, 'Проверете името, имейла и телефона.');
    if (!/^[A-Za-z][A-Za-z .'-]{2,44}$/.test(clean.cardholderName) || clean.billingAddress.length < 5 || clean.billingAddress.length > 50) throw new ApiError(400, 'Името на картодържателя трябва да е на латиница. Проверете адреса.');
    if (input.consent !== true || input.website) throw new ApiError(400, 'Необходимо е приемане на условията.');
    if (Object.values(clean).some(v => /[\u0000-\u001f\u007f]/.test(v))) throw new ApiError(400, 'Невалидни символи.');
    const seat = this.seats.find(s => s.id === clean.seatId); if (!seat) throw new ApiError(400, 'Невалидно място.');
    const { token, requestId, ...details } = clean;
    return { ...details, id: requestId, tokenHash: digest(token), total: this.config.prices[seat.tier], tier: seat.tier, fingerprint: digest(JSON.stringify(details)) };
  }
  async create(input, source = 'unknown') {
    if (!this.open()) throw new ApiError(403, 'Регистрацията все още не е отворена.');
    const data = this.validate(input);
    const existing = await this.store.get(data.id);
    if (existing) return this.repeat(existing, data);
    // Reservation rate limits are enforced in shared storage, never only in the browser.
    if (!await this.store.limit(digest(source), Math.floor(this.clock() / 60000), 5)) throw new ApiError(429, 'Твърде много заявки. Опитайте след минута.');
    const createdAt = Math.floor(this.clock() / 1000);
    const order = { ...data, status: 'pending', createdAt, termsVersion: this.config.termsVersion, termsAcceptedAt: createdAt, nonce: randomBytes(16).toString('hex').toUpperCase(), timestamp: new Date(this.clock()).toISOString().replace(/[-:TZ.]/g, '').slice(0, 14) };
    for (let attempt = 0; attempt < 5; attempt++) {
      order.bankOrder = String(randomInt(100000, 1000000));
      // Sign before claiming a seat; a broken bank configuration cannot strand an order.
      const checkout = this.payment.checkout(order);
      const result = await this.store.reserve(order);
      if (result === 'ok') return { reference: order.id, seatId: order.seatId, total: order.total, checkout };
      if (result === 'bankCollision') continue;
      const duplicate = await this.store.get(data.id);
      if (duplicate) return this.repeat(duplicate, data);
      throw new ApiError(409, 'Мястото вече не е свободно.');
    }
    throw new ApiError(503, 'Моля, опитайте отново.');
  }
  repeat(order, data) {
    if (!safeEqual(order.tokenHash, data.tokenHash) || !safeEqual(order.fingerprint, data.fingerprint)) throw new ApiError(409, 'Заявката е променена. Изберете мястото отново.');
    if (order.status !== 'pending' || this.clock() / 1000 - order.createdAt > 900) throw new ApiError(409, 'Тази заявка вече е обработена или е изтекла. Свържете се с организатора.');
    return { reference: order.id, seatId: order.seatId, total: order.total, checkout: this.payment.checkout(order) };
  }
  async status(id, token) {
    const order = await this.store.get(id);
    if (!order || !safeEqual(order.tokenHash, digest(String(token || '')))) throw new ApiError(404, 'Заявката не е намерена.');
    return { reference: order.id, seatId: order.seatId, total: order.total, status: order.status };
  }
  async bankResult(fields, expected = {}) {
    if (!this.payment.verify(fields)) throw new ApiError(400, 'Невалиден подпис на платежната система.');
    const order = await this.store.byBank(fields.ORDER);
    if (!order) throw new ApiError(404, 'Заявката не е намерена.');
    const type = expected.statusQuery ? '90' : '1';
    const nonce = expected.nonce || order.nonce;
    if (fields.TERMINAL !== this.payment.config.terminal || fields.TRTYPE !== type || !safeEqual(fields.NONCE, nonce)) throw new ApiError(400, 'Плащането не съответства на заявката.');
    if (expected.statusQuery && fields.TRAN_TRTYPE !== '1') throw new ApiError(400, 'Невалиден тип на операцията.');
    const success = fields.ACTION === '0' && fields.RC === '00';
    if (success && (fields.CURRENCY !== 'EUR' || fields.AMOUNT !== `${order.total}.00` || !fields.RRN || !fields.INT_REF || !fields.APPROVAL)) throw new ApiError(400, 'Невалидна сума или данни за плащане.');
    if (order.status !== 'pending') return order; // Signed replay cannot undo a final result.
    const definitiveDecline = fields.ACTION === '2' && /^[A-Z0-9]{2}$/.test(fields.RC) && fields.RC !== '00';
    const timedOut = expected.statusQuery && this.clock() / 1000 - order.createdAt > 960 && ['-19', '-25', '-40'].includes(fields.RC);
    if (success || definitiveDecline || timedOut) {
      const result = { status: success ? 'paid' : 'failed', paymentReference: fields.RRN || '', gatewayReference: fields.INT_REF || '', finalizedAt: Math.floor(this.clock() / 1000) };
      await this.store.finalize(order, result);
      return { ...order, ...result };
    }
    return order; // Unknown or nonfinal results keep the seat unavailable until reconciliation.
  }
  async reconcile() {
    if (!this.payment.ready) return { checked: 0, unresolved: 0 };
    const pending = await this.store.pending(); let checked = 0, unresolved = 0;
    for (const order of pending) {
      if (this.clock() / 1000 - order.createdAt < 960) continue;
      if (this.clock() / 1000 - order.createdAt >= 86400) { unresolved++; continue; }
      try {
        const nonce = randomBytes(16).toString('hex').toUpperCase();
        const response = await this.payment.query(order, nonce);
        const result = await this.bankResult(response, { statusQuery: true, nonce });
        checked++; if (result.status === 'pending') unresolved++;
      } catch { unresolved++; }
    }
    return { checked, unresolved };
  }
}
