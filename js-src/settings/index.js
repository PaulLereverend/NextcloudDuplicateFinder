import React from 'react'
import ReactDOM from 'react-dom'
import { Provider } from 'react-redux'

import { ToastContainer } from 'nextcloud-react'
import AppProivder from './AppProivder'
import store from './store'

ReactDOM.render(
  <React.StrictMode>
    <Provider store={store}>
      <AppProivder />
      <ToastContainer />
    </Provider>
  </React.StrictMode>,
  document.getElementById('duplicatefinder-settings')
)
