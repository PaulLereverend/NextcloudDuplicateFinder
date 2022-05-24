import React from 'react'

import {
  Setting, SettingsFrame,
  SettingsPageTitle, SettingsGrid,
  FilterBuilder, LoadingSpinner,
  useViewState, gettext
} from 'nextcloud-react'

export default function AppProivder (props) {
  const viewData = useViewState('SettingsView')
  if (viewData === undefined || viewData.settings === undefined) {
    return <LoadingSpinner style={{ marginTop: '5em' }} />
  }
  return (
    <SettingsFrame>
      <SettingsPageTitle
        help='https://github.com/PaulLereverend/NextcloudDuplicateFinder'
        label={'(' + gettext('Version: ') + (viewData.settings.installed_version || 'n.a.') + ')'}
        description={gettext('Adjust these settings to make the process of finding duplicates your own.')}
      >
        {gettext('Duplicate Finder')}
      </SettingsPageTitle>
      <SettingsGrid>
        <Setting
          setting='ignore_mounted_files'
          type='checkbox'
          hint={gettext('When true, files mounted on external storage will be ignored. Computing the hash for an external file may require transferring the whole file to the Nextcloud server. So, this setting can be useful when you need to reduce traffic e.g if you need to pay for the traffic.')}
          value={viewData.settings.ignore_mounted_files}
        >
          {gettext('Ignore Mounted Files')}
        </Setting>
        <Setting
          setting='disable_filesystem_events'
          type='checkbox'
          hint={gettext('When true, the event-based detection will be disabled. This gives you more control when the hashes are generated.')}
          value={viewData.settings.disable_filesystem_events}
        >
          {gettext('Disable event-based detection')}
        </Setting>
        <Setting
          setting='backgroundjob_interval_find'
          type='number'
          variant='conversion'
          subject='time'
          unit='second'
          defaultDisplayUnit='day'
          hint={gettext('Interval in seconds in which the background job, to find duplicates, will be run.')}
          value={viewData.settings.backgroundjob_interval_find}
        >
          {gettext('Detection Interval')}
        </Setting>
        <Setting
          setting='backgroundjob_interval_cleanup'
          type='number'
          variant='conversion'
          subject='time'
          unit='second'
          defaultDisplayUnit='day'
          hint={gettext('Interval in seconds in which the clean-up background job will be run.')}
          value={viewData.settings.backgroundjob_interval_cleanup}
        >
          {gettext('Cleanup Interval')}
        </Setting>
      </SettingsGrid>
      <h3 style={{ fontWeight: 'bold' }}>{gettext('Ignored Files')}</h3>
      <FilterBuilder filter={viewData ? viewData.filter : undefined} />
    </SettingsFrame>
  )
}
