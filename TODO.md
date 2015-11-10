1. User interface
2. Login system
2.1 Secure Credentials storage system
2.2 Friend list auto retrieving
2.3 Friends auto match
2.4 Friends info storage
3. Manual sync
4. Auto sync https://doc.owncloud.org/server/8.0/developer_manual/app/backgroundjobs.html


*--------------------------*
Use https://graph.facebook.com/user_id/picture?height=1000 to grab full size image
Use https://m.facebook.com/friends/center/friends/?ppk=x where x is the page.
--> use multiple request until results < 10 per page (end of the list)
Use a note in user contacts to store the fb id (so it can manually be set)
