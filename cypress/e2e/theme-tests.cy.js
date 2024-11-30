describe('WordPress Theme Tests', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  it('Lataa etusivu onnistuneesti', () => {
    cy.get('body').should('be.visible')
    cy.title().should('not.be.empty')
  })

  it('Navigointivalikko toimii', () => {
    cy.get('nav').should('be.visible')
    cy.get('nav a').should('have.length.gt', 0)
  })

  it('Hakutoiminto toimii', () => {
    cy.get('input[type="search"]').should('be.visible')
    cy.get('input[type="search"]').type('test{enter}')
    cy.url().should('include', '?s=test')
  })

  it('Mobiilivalikko toimii', () => {
    cy.viewport('iphone-6')
    cy.get('.mobile-menu-toggle').should('be.visible')
    cy.get('.mobile-menu-toggle').click()
    cy.get('.mobile-menu').should('be.visible')
  })

  it('Kuvien laiska lataus toimii', () => {
    cy.get('img[loading="lazy"]').should('exist')
  })

  it('Lomakkeiden validointi toimii', () => {
    cy.get('form').first().within(() => {
      cy.get('input[type="submit"]').click()
      cy.get('.error-message').should('be.visible')
    })
  })

  it('Suorituskykyoptimointien header-tagit ovat paikallaan', () => {
    cy.document().then((doc) => {
      expect(doc.head).to.contain.html('preload')
      expect(doc.head).to.contain.html('dns-prefetch')
    })
  })

  it('Kriittinen CSS on ladattu', () => {
    cy.get('style[data-critical="true"]').should('exist')
  })

  it('Service Worker on rekisteröity', () => {
    cy.window().then((win) => {
      expect(win.navigator.serviceWorker).to.not.be.undefined
    })
  })
})
