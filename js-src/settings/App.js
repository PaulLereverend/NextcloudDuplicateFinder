import React from 'react'

import PageTitle from './components/PageTitle'
import Setting from './components/Setting'
import SettingsFrame from './components/SettingsFrame'
import SettingsGrid from './components/SettingsGrid'

export default function AppProivder (props) {
  return <SettingsFrame>
    <PageTitle
      help="https://github.com/PaulLereverend/NextcloudDuplicateFinder"
      description="Adjust these settings to make the process of finding duplicates your own." >
        Duplicate Finder
    </PageTitle>
    <SettingsGrid>
        <Setting type="checkbox" hint="">Ignore Mounted Files</Setting>
        <Setting type="checkbox" hint="">Disable event-based detection</Setting>
        <Setting type="number" hint="">Cleanup Interval</Setting>
        <Setting type="number" hint="">Detection Interval</Setting>
    </SettingsGrid>
  </SettingsFrame>
}
