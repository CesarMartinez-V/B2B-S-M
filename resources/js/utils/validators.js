export const required = (value) => String(value ?? '').trim().length > 0;

export const sameAs = (value, confirmation) => value === confirmation;

export const minLength = (value, length) => String(value ?? '').length >= length;
