# Unclaimed Money

In the United States, alone, there are over $40 billion in unclaimed money and property being held by the state and federal governments.  Some of this money has been sitting unclaimed for years, while other money has just been added to the unclaimed money registries.  

It is easy to search for a property to find out it if it belongs to you, and easy to claim property that does.  That leaves only one question: is any of it yours?

Each state has its own unclaimed property database.  Select the states you want to search here.

# Requirements:

When entering the first name, last name or business name and state. The program searches data in respective state site and returns a list of matched data. This program is used as API.

1 - For scraping data, we use curl calls and simple HTML dom libraries.

2 - To Scrape data from websites which are having captcha we used captcha solving services.

3 - To prevent from getting blocked we used the proxy network and it could handle thousands of requests.

4 - We implemented the API according to the requirements also added additional functionalities.

# 4.1 - Store user search data

User search with some keywords like the first name or last name or business name. Using API we are collecting search results from their server and showing in our site. Then we plan to store those results in our server which has been sent by all APIs. In that way, we get those site's data on our server. So later when the user searches for the same person we show it from our DB. We update this data in regular interval to show the latest and relevant result. It also reduced the API load.


# 4.2 - OAuth token

The Key Authorization policy is an efficient way of securing restricting access to API endpoints for applications through API keys. Every request that the user sends to the API needs to identify by our application. We provide a private key to each user who will use our site.  In that way, we check and authorize users. But to prevent further malicious attack we provide a token key which will exist for a certain time period. In order to use the Key Authorization policy, token-auth credentials must be created for them. after token expires user has to request for another token for further use.

# 4.3 - Advanced search 

In our search form, the user can search with first name, last name, state and or business name. But we have noticed that in the different sites there are different search fields have been used. So we provided an advanced search for the user. In that way, we can make our search results more accurate.

# 4.4 - Access report

After search If you believe that the property belongs to you, you can generally make an online claim. So we provided functionality that allows user to directly visit the site that we have pulled the data from and take necessary action as per the site’s guidelines.

5 - Created and implemented REST API in unclaimedmoneyfinder.org, beenverified.com and peoplelooker.com sites.

