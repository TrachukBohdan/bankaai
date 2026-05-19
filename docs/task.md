# Test Assignment for the Position of

# Fullstack Web Developer

# (Vue.js + PHP Laravel / Python)

You need to implement:

* a backend using
  * PHP — Laravel, or
  * Python — without a framework
* a frontend using Vue 2 or Vue 3.

Add instructions for running the project to the repository.

Using AI agents during the implementation is mandatory.
However, we expect you to improve the generated code according to your professional level.
An implementation produced solely by AI usually does not allow passing to the next stage of the selection process.

The time limit is 8 working hours.
During this time, implement as much of the functionality listed below as you can without sacrificing quality.
The level of quality should be as close to real production as possible.

## **Using AI**

You must add a file to the repository (e.g. `LLM_INSTRUCTIONS.md`) describing:

* which tasks were solved with the help of AI
* which prompts were used, and for which LLMs
* which parts of the code were refined manually
* which decisions were changed after generation

## **UI/UX**

* the design and structure of the interface are at the candidate's discretion
* what matters is:
  * ease of use
  * clarity of interaction
  * compliance with generally accepted UI / UX patterns

# Backend: integration with a banking API to collect information

Develop an API for fetching and displaying the current exchange rates of several banks, as well as statistics on rate changes over a selected period; and the ability to get the nearest bank branches depending on the user's location.

Required functionality:

1. Input data
- list of currencies (USD, EUR, GBP, CHF, PLN)
- list of banks (5 of your choice), including information about them (name, description, logo, website, phone number, email, legal address, rating)
- list of bank branches (address, coordinates, phone number, branch name)


2. Integration with a banking API
- Use the MinFin API to obtain current exchange rates
- Use the NBU (National Bank of Ukraine) API to obtain current exchange rates
- Configure regular automatic updates of exchange rates
- Configure regular automatic updates of bank branch data


3. API capabilities
- Get a list of banks, along with information about them (name, logo, rating, phone number, email)
- Get all information about a specific bank with current exchange rates and a list of branches
- Get the nearest bank branches depending on the user's location
- Get the list of currencies
- Get the current list of exchange rates with the ability to filter the data by specific banks and currencies
- Get the current NBU exchange rates and the average rate across all banks


4. Extended functionality
- User account
  * User registration and authentication
  * Ability to edit account data
- History of exchange rate changes
  * Implement a mechanism for collecting history of significant changes in exchange rates (e.g., 5%)
  * Add the ability to fetch the history of significant changes during a defined period
- Notifications
  * Implement a mechanism for notifying the user by email about important changes in exchange rates; additionally provide the ability to subscribe to specific currencies or banks
  * Allow the user to enable/disable notifications about significant changes in exchange rates
- Statistics
  * Provide the user with statistics on exchange rate changes over a defined period, with the ability to filter the data by specific banks and currencies

## **API resources**

currency list
https://minfin.com.ua/api/currency/list?type=money&locale=uk

exchange rates
https://minfin.com.ua/api/currency/rates/banks/{currency_code}

exchange rates NBU
https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json

banks list
https://finance.ua/banks/api/organizationsList?locale=uk

bank branches list
https://finance.ua/api/organization/v1/branches?slug={bank_slug}&locale=uk

# **Frontend**

You need to implement a frontend application in Vue 2 or Vue 3 that works with the backend API.
The application must provide the ability to:

* view the list of banks and detailed information about a bank
* view current exchange rates
* filter rates by banks and currencies
* view the NBU rate and the average rate across banks
* view statistics of rate changes over a selected period
* get the nearest bank branches based on the user's geolocation

Additionally:

* it is desirable to implement visualization of statistics (a chart)
* it is desirable to use a map to display branches
* you may implement: user registration, authorization, and profile management
* you may implement: the ability to configure subscriptions to notifications about rate changes

## **Technical requirements**

* use Vue 2 or Vue 3
* use a state manager (Pinia or Vuex)
* use Vue Router
* work with the API through an HTTP client (Axios or equivalent)
* responsive interface (desktop + mobile)