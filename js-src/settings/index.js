import React from 'react'
import ReactDOM from 'react-dom'
import { Provider } from 'react-redux'

import { ToastContainer } from 'nextcloud-react'
import App from './App'
import store from './store'

ReactDOM.render(
  <React.StrictMode>
    <Provider store={store}>
      <App />
      <ToastContainer />
    </Provider>
  </React.StrictMode>,
  document.getElementById('duplicatefinder-settings')
)
