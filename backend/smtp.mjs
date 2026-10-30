import nodemailer from 'nodemailer';
export class Smtp {
  constructor({ host, port = 587, user, pass, from = user }) {
    this.ready = Boolean(host && user && pass);
    this.from = from;
    if (this.ready) this.transport = nodemailer.createTransport({ host, port: Number(port), secure: Number(port) === 465, requireTLS: Number(port) !== 465, auth: { user, pass }, connectionTimeout: 5000, greetingTimeout: 5000, socketTimeout: 8000 });
  }
  send({ to, subject, text }) { return this.transport.sendMail({ from: `ETALON CARE <${this.from}>`, to, subject, text }); }
}
