import React from 'react'

import { convert, getConverterUnits } from '../utils/ConversionUtils'

export default function ConversionField (props) {
  const { subject, onBlur, onChange, defaultDisplayUnit, unit, value, style, ...otherProps } = { ...props }
  const [displayUnit, setUnit] = React.useState(defaultDisplayUnit || unit)
  const [currentValue, setValue] = React.useState(convert(value || 0, subject, unit, displayUnit, 1))
  const [targetValue, setTargetValue] = React.useState(value || 0)

  const handleChange = (type, newValue) => {
    let v, u
    if (type === 'value') {
      v = newValue
      u = displayUnit
      setValue(newValue)
    } else {
      v = currentValue
      u = newValue
      setUnit(newValue)
    }
    const targetValue = convert(v, subject, u, unit, 1)
    setTargetValue(targetValue)
    if (onChange) {
      onChange(targetValue)
    }
  }

  const handleBlur = (e) => {
    if (onBlur) {
      onBlur(targetValue)
    }
  }

  let styles = [{ position: 'relative' }]
  if (Array.isArray(style)) {
    styles = styles.concat(style)
  } else {
    styles.push(style)
  }
  return (
    <div css={styles}>
      <input
        value={currentValue}
        onChange={(e) => handleChange('value', e.target.value)}
        onBlur={handleBlur}
        css={{ width: '100%', margin: '0 !important', height: '4em !important', padding: '6px 7px !important', textAlign: 'right', paddingRight: '21% !important' }}
        {...otherProps}
      />
      <select
        value={displayUnit}
        onChange={(e) => handleChange('unit', e.target.value)}
        onBlur={handleBlur}
        css={{ position: 'absolute', top: 0, bottom: 0, right: 0, margin: 0, padding: 0, height: '4em', width: '20%' }}
      >
        {getConverterUnits(subject).map((v, i) => <option value={v} key={i}>{v}</option>)}
      </select>
      <span css={{ position: 'absolute', bottom: 0, left: '7px', fontSize: '0.8em' }}>{targetValue} {unit}</span>
    </div>
  )
}
