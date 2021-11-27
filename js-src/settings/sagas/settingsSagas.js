import { takeLatest, takeEvery, select } from 'redux-saga/effects'
import uniqueId from 'lodash/uniqueId'
import {
  createReducerConstants, setViewData,
  API, showErrorToast,
  showSuccessToast, setStep,
  removeStep, gettext
} from 'nextcloud-react'

const reducerConstants = createReducerConstants()

export default [
  watchForView,
  watchForAction
]

function handleFilter (event, filter = []) {
  if (event.action === 'filter_builder_add_or_condtion') {
    filter.push({
      id: uniqueId('filter_group_'),
      conditions: [{
        id: uniqueId('filter_condition_')
      }]
    })
    return [...filter]
  } else if (event.action === 'filter_builder_delete_or_condtion') {
    return filter.filter((g) => g.id !== event.payload.group)
  }
  const group = filter.find(g => g.id === event.payload.group)
  if (event.action === 'filter_builder_save_condtion') {
    const condition = group.conditions.find(c => c.id === event.payload.id)
    condition.attribute = event.payload.attribute
    condition.operator = event.payload.operator
    condition.value = event.payload.value
  } else if (event.action.endsWith('_and_condtion')) {
    if (event.action === 'filter_builder_add_and_condtion') {
      group.conditions.push({
        id: uniqueId('filter_condition_')
      })
    }
    if (event.action === 'filter_builder_delete_and_condtion') {
      group.conditions = group.conditions.filter((c) => {
        return c.id !== event.payload.id
      })
    }
  }
  return [...filter]
}

function * changeFilter (event) {
  if (event && event.payload && event.payload.action.startsWith('filter_')) {
    const viewData = yield select((state) => 'SettingsView' in state.app.views ? state.app.views.SettingsView : {})
    if (event.payload.action === 'filter_builder_save') {
      const savedFilter = viewData && viewData.filter ? viewData.filter.map((g) => g.conditions) : []
      yield saveSetting('ignored_files', savedFilter, viewData.filter)
    } else {
      viewData.filter = handleFilter(event.payload, viewData.filter)
      yield setViewData('SettingsView', { ...viewData })
    }
  }
}

function * setSetting (viewData, key, value) {
  viewData.settings[key] = value
  yield setViewData('SettingsView', {
    ...viewData,
    settings: viewData.settings
  })
}

function * changeSetting (event) {
  if (event && event.payload && event.payload.action === 'setting_changed') {
    const viewData = yield select((state) => 'SettingsView' in state.app.views ? state.app.views.SettingsView : {})
    const oldValue = viewData.settings[event.payload.payload.setting]
    yield setSetting(viewData, event.payload.payload.setting, event.payload.payload.value)
    yield saveSetting(event.payload.payload.setting, event.payload.payload.value, oldValue)
  }
}

function * watchForAction () {
  yield takeEvery(reducerConstants.app.f.ACTION_REQUESTED, changeFilter)
  yield takeEvery(reducerConstants.app.f.ACTION_REQUESTED, changeSetting)
}

function * watchForView () {
  yield takeLatest(reducerConstants.app.f.VIEW_LOADED, loadSettings)
}

function * loadSettings () {
  yield setStep('settings', gettext('Loading Settings'))
  let response = yield API.get('duplicatefinder', 'Settings')
  if (response.status === 200) {
    response = yield response.json()
    const filter = []
    if (response.data.ignored_files !== undefined && Array.isArray(response.data.ignored_files)) {
      for (const g of response.data.ignored_files) {
        filter.push({
          id: uniqueId('filter_group_'),
          conditions: g.map((r) => {
            return { ...r, id: uniqueId('filter_condition_') }
          })
        })
      }
    }
    yield setViewData('SettingsView', { settings: response.data, filter })
    yield removeStep('settings')
  } else {
    yield setStep('settings', gettext('Failed to load settings'), { state: 'error' })
    yield showErrorToast(gettext('Failed to load settings'))
  }
}

function * saveSetting (key, value, oldValue) {
  const newConfig = {}
  newConfig[key] = value
  const response = yield API.patch('duplicatefinder', 'Settings', undefined, { newConfig })
  if (response.status === 200) {
    yield showSuccessToast(gettext('Saved setting ' + key))
  } else {
    yield showErrorToast(gettext('Failed to save setting ' + key))
    const viewData = yield select((state) => 'SettingsView' in state.app.views ? state.app.views.SettingsView : {})
    yield setSetting(viewData, key, oldValue)
  }
}
