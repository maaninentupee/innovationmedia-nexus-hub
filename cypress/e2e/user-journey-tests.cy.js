describe('Käyttäjäpolkutestit', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  describe('Rekisteröityminen ja kirjautuminen', () => {
    it('Käyttäjä voi rekisteröityä', () => {
      cy.get('.register-link').click()
      cy.get('#user_login').type('testuser')
      cy.get('#user_email').type('test@example.com')
      cy.get('#user_pass').type('TestPassword123!')
      cy.get('#signup-form').submit()
      cy.get('.success-message').should('be.visible')
    })

    it('Käyttäjä voi kirjautua sisään', () => {
      cy.get('.login-link').click()
      cy.get('#user_login').type('testuser')
      cy.get('#user_pass').type('TestPassword123!')
      cy.get('#login-form').submit()
      cy.get('.logged-in').should('exist')
    })
  })

  describe('WooCommerce ostoskoriprosessi', () => {
    beforeEach(() => {
      cy.login('testuser', 'TestPassword123!')
    })

    it('Käyttäjä voi lisätä tuotteen ostoskoriin', () => {
      cy.visit('/shop')
      cy.get('.product').first().within(() => {
        cy.get('.add_to_cart_button').click()
      })
      cy.get('.cart-count').should('have.text', '1')
    })

    it('Käyttäjä voi suorittaa kassaprosessin', () => {
      cy.visit('/cart')
      cy.get('.checkout-button').click()
      
      // Täytä toimitusosoite
      cy.get('#billing_first_name').type('Test')
      cy.get('#billing_last_name').type('User')
      cy.get('#billing_address_1').type('Test Street 123')
      cy.get('#billing_postcode').type('00100')
      cy.get('#billing_city').type('Helsinki')
      cy.get('#billing_phone').type('0401234567')
      cy.get('#billing_email').type('test@example.com')

      // Valitse maksutapa
      cy.get('#payment_method_bacs').check()
      
      // Vahvista tilaus
      cy.get('#place_order').click()
      
      // Tarkista tilausvahvistus
      cy.get('.woocommerce-order-received').should('exist')
    })
  })
})

// Responsiivisuustestit
describe('Responsiivisuustestit', () => {
  const sizes = [
    ['iphone-6', 375, 667],
    ['ipad-2', 768, 1024],
    ['macbook-13', 1280, 800],
    ['1080p', 1920, 1080]
  ]

  sizes.forEach((size) => {
    it(`Testaa responsiivisuus koolla ${size[0]}`, () => {
      cy.viewport(size[1], size[2])
      cy.visit('/')
      
      // Tarkista navigaatio
      if (size[1] < 768) {
        cy.get('.mobile-menu-toggle').should('be.visible')
      } else {
        cy.get('.main-navigation').should('be.visible')
      }
      
      // Tarkista grid-asettelu
      cy.get('.site-content').should('be.visible')
      cy.get('.product-grid').should('have.css', 'display', 'grid')
    })
  })
})
