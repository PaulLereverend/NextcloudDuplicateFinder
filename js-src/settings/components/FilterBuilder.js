import React from 'react'
import ConversionField from './ConversionField'

import { useActionDispatcher, Button } from 'nextcloud-react'

export default function FilterBuilder (props) {
  const dispatchAction = useActionDispatcher()
  let content
  if (props.filter === undefined || !Array.isArray(props.filter) || props.filter.length === 0) {
    content = <button onClick={(e) => dispatchAction(e, 'filter_builder_add_or_condtion')}>Add Condition</button>
  } else {
    content = props.filter.map((filterGroup) => (
      <FilterGroup
        {...filterGroup}
        key={filterGroup.id}
      />
    ))
  }
  return (
    <>
      {content}
      <div css={{ display: 'block', textAlign: 'right' }}>
        <Button primary onClick={(e) => dispatchAction(e, 'filter_builder_save')}>Save</Button>
      </div>
    </>
  )
}

function FilterGroup (props) {
  return (
    <>
      {props.conditions.map((condition, i) => (
        <FilterCondtion
          {...condition}
          key={condition.id}
          group={props.id}
          index={i}
        />))}
    </>
  )
}

function FilterCondtion (props) {
  const dispatchAction = useActionDispatcher()
  const [attribute, setAttribute] = React.useState(props.attribute || 'filename')
  const [operator, setOperator] = React.useState(props.operator || '=')
  const [value, setValue] = React.useState(props.value || '')

  const saveChange = (k, nV) => {
    if (k === 'attribute') {
      setAttribute(nV)
    } else if (k === 'operator') {
      setOperator(nV)
    } else {
      console.log('Saving Value', k, nV)
      setValue(nV)
    }

    console.log('Saving Value', k, nV, {
      id: props.id, group: props.group, attribute, operator, value
    })
    dispatchAction(undefined, 'filter_builder_save_condtion', {
      id: props.id, group: props.group, attribute, operator, value
    })
  }

  const handleAction = (a, e) => {
    dispatchAction(e, a, { id: props.id, group: props.group })
  }

  return (
    <div style={{ display: 'flex', marginLeft: props.index > 0 ? '2em' : '0em' }}>
      <select value={attribute} onChange={(e) => saveChange('attribute', e.target.value)}>
        <option value='filename'>Filename</option>
        <option value='size'>Size</option>
        <option value='path'>Path</option>
      </select>
      <select value={operator} onChange={(e) => saveChange('operator', e.target.value)}>
        {
            (attribute === 'filename' || attribute === 'path'
              ? ['=', 'GLOB', 'REGEX']
              : ['>', '>=', '<', '<=', '=']).map((o, i) => <option value={o} key={i}>{o}</option>)
        }
      </select>
      {attribute === 'filename' || attribute === 'path'
        ? (
          <input
            style={{ flexGrow: 1 }}
            type='text'
            onBlur={(e) => saveChange('value', e.target.value)}
            onChange={(e) => setValue(e.target.value)}
            value={value}
          />
          )
        : (
          <ConversionField
            style={{ flexGrow: 1 }}
            value={value}
            subject='bit' unit='byte' defaultDisplayUnit='megabyte'
            onChange={(v) => setValue(v)}
            onBlur={(v) => saveChange('value', v)}
          />
          )}
      <div>
        {props.index === 0
          ? (
            <>
              <button onClick={handleAction.bind(undefined, 'filter_builder_add_and_condtion')}>AND</button>
              <button onClick={handleAction.bind(undefined, 'filter_builder_add_or_condtion')}>OR</button>
              <button onClick={handleAction.bind(undefined, 'filter_builder_delete_or_condtion')}>X</button>
            </>)
          : <button onClick={handleAction.bind(undefined, 'filter_builder_delete_and_condtion')}>X</button>}
      </div>
    </div>
  )
}
