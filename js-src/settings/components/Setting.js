import React from 'react'
import uniqueId from 'lodash/uniqueId'
import { useActionDispatcher } from 'nextcloud-react'

import ConversionField from './ConversionField'

export default function Setting (props) {
  const { children, setting, ...otherProps } = { ...props }
  const id = uniqueId('setting_')
  const dispatchAction = useActionDispatcher()
  const handleChangedSetting = (newValue) => {
    dispatchAction(undefined, 'setting_changed', { setting, value: newValue })
  }
  const field = <Input id={id} {...otherProps} handleChange={handleChangedSetting} />
  const label = <label css={{ flex: 1, alignSelf: 'center' }} htmlFor={id}>{children}</label>
  if (props.type === 'checkbox') {
    return <Row>{field}{label}</Row>
  }
  return <Row>{label}{field}</Row>
}

function Input (props) {
  const css = [{}, props.css]

  if (props.type !== 'checkbox') {
    css[0].flex = 1
  } else {
    css[0].marginRight = '1em'
  }
  const { handleChange, value, ...otherProps } = { ...props }
  if (props.variant === 'conversion') {
    return <ConversionField style={css} value={value} onBlur={handleChange} {...otherProps} />
  } else if (props.type === 'checkbox') {
    return <input css={css} checked={value} {...otherProps} onChange={(e) => handleChange(e.target.checked)} />
  } else {
    return <input css={css} value={value} {...otherProps} onChange={(e) => handleChange(e.target.value)} />
  }
}

function Row (props) {
  return (
    <div css={{ display: 'flex' }}>
      {props.children}
    </div>
  )
}
