import React from 'react'

export default function PageTitle(props) {
    return <>
        <h2 className="inlineblock" >{props.children}</h2>
        {props.help ? <a target="_blank" rel="noreferrer" className="icon-info" title="Open Help" href={props.help}></a>: <></>}
        {props.description ? <p className="settings-hint">{props.description}</p> : <></>}
    </>

}