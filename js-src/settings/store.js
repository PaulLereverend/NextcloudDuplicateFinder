import { createStore, applyMiddleware } from 'redux'
import { composeWithDevTools } from 'redux-devtools-extension'
import createSagaMiddleware from 'redux-saga'
import { persistStore, persistReducer } from 'redux-persist'
import storage from 'redux-persist/lib/storage'
import autoMergeLevel2 from 'redux-persist/lib/stateReconciler/autoMergeLevel2'
import { createBrowserHistory } from 'history'

import { createRootReducer, createAppSagas } from 'nextcloud-react'

import settingsSagas from './sagas/settingsSagas'

export const history = createBrowserHistory()

const sagaMiddleware = createSagaMiddleware()
const persistConfig = {
  key: 'root',
  storage,
  whitelist: [],
  stateReconciler: autoMergeLevel2
}

const persistedReducer = persistReducer(persistConfig, createRootReducer({
}, history))

const store = createStore(
  persistedReducer,
  undefined,
  composeWithDevTools(
    applyMiddleware(
      sagaMiddleware
    )
  )
)

function * sagas () {
  yield createAppSagas([
    ...settingsSagas
  ])
}

export const persistor = persistStore(store)
sagaMiddleware.run(sagas)
export default store
