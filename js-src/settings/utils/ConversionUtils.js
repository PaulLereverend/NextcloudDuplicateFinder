
const conversionRates = {
  time: {
    second: 1,
    minute: 60,
    hour: 60 * 60,
    day: 60 * 60 * 24,
    week: 60 * 60 * 24 * 7,
    month: 60 * 60 * 24 * 30
  },
  bit: {
    bit: 1,
    byte: 8,
    kilobit: 1000 * 1,
    kilobyte: 1000 * 8,
    megabit: 1000 * 1000 * 1,
    megabyte: 1000 * 1000 * 8,
    gigabit: 1000 * 1000 * 1000 * 1,
    gigabyte: 1000 * 1000 * 1000 * 8
  }
}

export function getConverterUnits (subject) {
  return Object.keys(conversionRates[subject])
}

export function convert (value, subject, sourceUnit, targetUnit, precision) {
  let result
  const rates = conversionRates[subject]
  if (sourceUnit === targetUnit) {
    result = value
  } else if (rates[sourceUnit] !== undefined && rates[targetUnit] !== undefined) {
    result = (value * rates[sourceUnit]) / rates[targetUnit]
  } else {
    console.log('Convert NA')
    return undefined
  }
  if (precision !== undefined) {
    console.log('Convert R', result)
    return Math.round(result * precision) / precision
  } else {
    console.log('Convert N', result)
    return result
  }
}
