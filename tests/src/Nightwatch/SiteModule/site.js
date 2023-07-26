module.exports = {
    '@tags': ['operations'],
    // before: function(browser) {
    //     browser
    //         .drupalInstall();
    // },
    // after: function(browser) {
    //     browser
    //         .drupalUninstall();
    // },
    'Visit the site status page': (browser) => {
        browser
            .drupalLoginAsAdmin()
            .drupalRelativeURL('/admin/about/site')
            .waitForElementVisible('body', 1000)
            .assert.containsText('body', 'About this site')
            .click('#button--action')
            .assert.containsText('body', 'via the Save Report button')
            .end();
    },

};