import React from 'react'
import uniqueId from 'lodash/uniqueId'

export default function Setting(props) {
    const {children, ...otherProps} = {...props}
    const id = uniqueId('setting_')
    const field = <Input  id={id} {...otherProps} />
    const label = <label style={{flex:1, alignSelf: 'center'}} htmlFor={id}>{children}</label>
    if(props.type === 'checkbox'){
        return <Row>{field}{label}</Row>
    }
    return <Row>{label}{field}</Row>
}

function Input(props){
    const style = {};
    if(props.type !== 'checkbox'){
        style.flex = 1
    } else {
        style.marginRight = '1em'
    }
    return <input style={style} {...props}/>
}

function Row(props){
    return <div style={{display:'flex'}}>
        {props.children}
    </div>
}