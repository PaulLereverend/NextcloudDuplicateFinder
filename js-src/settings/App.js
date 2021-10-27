import React from 'react'

import PageTitle from './components/PageTitle'
import Setting from './components/Setting'
import SettingsFrame from './components/SettingsFrame'
import SettingsGrid from './components/SettingsGrid'
import FilterBuilder from './components/FilterBuilder'

import { useViewState, LoadingSpinner } from 'nextcloud-react'

export default function AppProivder (props) {
  const viewData = useViewState('SettingsView')
  if (viewData === undefined || viewData.settings === undefined) {
    return <LoadingSpinner style={{ marginTop: '5em' }} />
  }
  return (
    <SettingsFrame>
      <PageTitle
        help='https://github.com/PaulLereverend/NextcloudDuplicateFinder'
        description='Adjust these settings to make the process of finding duplicates your own.'
      >
        Duplicate Finder (Version: {viewData.settings.installed_version || 'n.a.'})
      </PageTitle>
      <SettingsGrid>
        <Setting
          setting='ignore_mounted_files'
          type='checkbox'
          hint=''
          value={viewData.settings.ignore_mounted_files}
        >
          Ignore Mounted Files
        </Setting>
        <Setting
          setting='disable_filesystem_events'
          type='checkbox'
          hint=''
          value={viewData.settings.disable_filesystem_events}
        >
          Disable event-based detection
        </Setting>
        <Setting
          setting='backgroundjob_interval_find'
          type='number'
          variant='conversion'
          subject='time'
          unit='second'
          defaultDisplayUnit='day'
          hint=''
          value={viewData.settings.backgroundjob_interval_find}
        >
          Cleanup Interval
        </Setting>
        <Setting
          setting='backgroundjob_interval_cleanup'
          type='number'
          variant='conversion'
          subject='time'
          unit='second'
          defaultDisplayUnit='day'
          hint=''
          value={viewData.settings.backgroundjob_interval_cleanup}
        >
          Detection Interval
        </Setting>
      </SettingsGrid>
      <h3 style={{ fontWeight: 'bold' }}>Ignored Files</h3>
      <FilterBuilder filter={viewData ? viewData.filter : undefined} />
    </SettingsFrame>
  )
}
// 'ignored_files' => $this->configService->getIgnoreConditions()
