import React from 'react'

export default function SettingsGrid(props) {
    return <div style={{display:'flex', flexDirection:'column'}}>
        {props.children}
        <div style={{display:'block'}}>
            <button style={{float:'right'}}>Save</button>
        </div>
    </div>

}