import { createPrivateKey, createPublicKey, sign, verify } from 'node:crypto';
const SALE = ['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'TIMESTAMP', 'NONCE', 'RFU'];
const STATUS = ['TERMINAL', 'TRTYPE', 'ORDER', 'NONCE'];
const RESPONSE = ['ACTION', 'RC', 'APPROVAL', 'TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'RRN', 'INT_REF', 'PARES_STATUS', 'ECI', 'TIMESTAMP', 'NONCE', 'RFU'];
export function macString(fields, names) {
  return names.map(name => {
    const value = name === 'RFU' ? '' : fields[name];
    if (value === undefined || value === null || value === '') return '-';
    if (typeof value !== 'string' || value.length > 1000) throw new Error('Invalid signature field');
    return `${Buffer.byteLength(value, 'utf8')}${value}`;
  }).join('');
}
export const responseString = fields => macString(fields, RESPONSE);
export class Borica {
  constructor(config) {
    this.config = config;
    this.endpoint = config.mode === 'production' ? 'https://3dsgate.borica.bg/cgi-bin/cgi_link' : 'https://3dsgate-dev.borica.bg/cgi-bin/cgi_link';
    this.ready = Boolean(config.terminal && config.merchant && config.name && config.privateKey && config.publicKey && config.backref);
    if (this.ready) {
      if (!/^[A-Z0-9]{8}$/.test(config.terminal) || !/^\d{10}$/.test(config.merchant)) throw new Error('Invalid BORICA merchant configuration');
      if (!new URL(config.backref).protocol.startsWith('https')) throw new Error('HTTPS callback required');
      this.privateKey = createPrivateKey({ key: config.privateKey, passphrase: config.passphrase });
      this.publicKey = createPublicKey(config.publicKey);
      if (this.privateKey.asymmetricKeyType !== 'rsa' || this.privateKey.asymmetricKeyDetails.modulusLength !== 2048) throw new Error('BORICA requires RSA-2048');
    }
  }
  signature(fields, names) { return sign('RSA-SHA256', Buffer.from(macString(fields, names)), this.privateKey).toString('hex').toUpperCase(); }
  checkout(order) {
    if (!this.ready) throw new Error('Payment unavailable');
    const fields = {
      TERMINAL: this.config.terminal, TRTYPE: '1', AMOUNT: `${order.total}.00`, CURRENCY: 'EUR', ORDER: order.bankOrder,
      DESC: `ETALON Conference ${order.seatId}`, MERCHANT: this.config.merchant, MERCH_NAME: this.config.name,
      MERCH_URL: this.config.siteUrl, COUNTRY: 'BG', LANG: 'BG', TIMESTAMP: order.timestamp, NONCE: order.nonce,
      BACKREF: this.config.backref, ADDENDUM: 'AD,TD', 'AD.CUST_BOR_ORDER_ID': `${order.bankOrder}${order.id.replaceAll('-', '').slice(0, 16)}`,
      M_INFO: Buffer.from(JSON.stringify({ cardholderName: order.cardholderName, email: order.email, billAddrLine1: order.billingAddress, threeDSRequestorChallengeInd: '04' })).toString('base64')
    };
    fields.P_SIGN = this.signature(fields, SALE);
    return { url: this.endpoint, fields };
  }
  verify(fields) {
    if (!this.ready || !/^[0-9A-Fa-f]{512}$/.test(fields.P_SIGN || '')) return false;
    try { return verify('RSA-SHA256', Buffer.from(responseString(fields)), this.publicKey, Buffer.from(fields.P_SIGN, 'hex')); }
    catch { return false; }
  }
  async query(order, nonce) {
    const fields = { TERMINAL: this.config.terminal, TRTYPE: '90', ORDER: order.bankOrder, TRAN_TRTYPE: '1', NONCE: nonce };
    fields.P_SIGN = this.signature(fields, STATUS);
    const response = await fetch(this.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(fields), signal: AbortSignal.timeout(10000) });
    if (!response.ok) throw new Error('Bank status unavailable');
    return response.json();
  }
}
