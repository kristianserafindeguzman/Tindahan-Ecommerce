export const PASSWORD_REQUIREMENTS =
  'At least 8 characters, including one uppercase letter, one lowercase letter, and one symbol.'

export function validatePassword(value, t = text => text) {
  if (typeof value !== 'string' || value.length === 0) {
    return t('Password is required.')
  }

  // Count Unicode characters consistently with Laravel and PHP's mb_strlen.
  const checks = [
    [[...value].length >= 8, 'Password must be at least 8 characters.'],
    [
      /\p{Lu}/u.test(value),
      'Password must contain at least one uppercase letter.'
    ],
    [
      /\p{Ll}/u.test(value),
      'Password must contain at least one lowercase letter.'
    ],
    [/[\p{P}\p{S}]/u.test(value), 'Password must contain at least one symbol.']
  ]
  const failed = checks.find(([passes]) => !passes)
  return failed ? t(failed[1]) : true
}
