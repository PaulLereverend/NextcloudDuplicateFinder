import React from 'react'

export default function SettingsGrid (props) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column' }}>
      {props.children}
    </div>
  )
}
