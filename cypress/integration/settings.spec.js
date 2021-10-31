// settings.spec.js created with Cypress
//
// Start writing your Cypress tests below!
// If you're unfamiliar with how Cypress works,
// check out the link below and learn how to write your first test:
// https://on.cypress.io/writing-first-test

describe('Settings Tests', () => {
  beforeEach(() => {
    cy.login('admin', 'admin')
    cy.visit('/settings/admin/duplicatefinder')
    cy.intercept('PATCH', '/apps/duplicatefinder/api/v1/Settings').as('updateSettings')
  })
  it('Settings can be visited and rendered', () => {
    cy.contains('Duplicate Finder')
  })
  it('Change ignore mounted files', () => {
    cy.contains('Ignore Mounted Files')
      .click()
    cy.wait('@updateSettings')
    cy.contains('Saved setting ignore_mounted_files')
    cy.contains('Ignore Mounted Files')
      .click()
    cy.wait('@updateSettings')
    cy.contains('Saved setting ignore_mounted_files')
  })
  it('Change Disable event-based detection', () => {
    cy.contains('Disable event-based detection')
      .click()
    cy.wait('@updateSettings')
    cy.contains('Saved setting disable_filesystem_events')
    cy.contains('Disable event-based detection')
      .click()
    cy.wait('@updateSettings')
    cy.contains('Saved setting disable_filesystem_events')
  })
  it('Change cleanup interval', () => {
    cy.contains('Cleanup Interval')
      .click().type('{selectall}20')
    cy.wait('@updateSettings')
    cy.contains('Saved setting backgroundjob_interval_cleanup')
    cy.contains('Cleanup Interval')
      .click().type('{selectall}2')
  })
  it('Change Detection Interval', () => {
    cy.contains('Detection Interval')
      .click().type('{selectall}20')
    cy.wait('@updateSettings')
    cy.contains('Saved setting backgroundjob_interval_find')
    cy.contains('Detection Interval')
      .click().type('{selectall}5')
  })
  it('Change Filter', () => {
    cy.contains('Add Condition')
      .click()
    cy.contains('AND')
      .click().click()
    cy.contains('OR')
      .click()
    cy.get("[value='filename']").parent('select').first().select('size').should('have.value', 'size')
    cy.get("[value='filename']").parent('select').eq(2).select('path').should('have.value', 'path')
    cy.contains('Save')
      .click()
    cy.contains('Saved setting ignored_files')
    cy.get('button').eq(2).click()
    cy.get('button').eq(2).click()
    cy.contains('Save')
      .click()
  })
})
