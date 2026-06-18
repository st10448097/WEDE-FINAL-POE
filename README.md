Overview 
Past Times is a simple web-based second-hand clothing store application. It allows users to register, log in, browse products, add items to a shopping cart, and complete a purchase.  Administrators can manage user accounts and oversee the system. The application is designed to be easy to use, with clear navigation and essential e-commerce functionality.
Setup instructions:
1. Start WampServer and ensure the icon is green
2. Open phpMyAdmin and create a database named: “clothesstore”
3. Place the project folder inside: C:\wamp64\www\ClothesStore
4. Run the setup script in your browser: https://localhost/ClothesStore/loadClothingStore.php
This will:
•	Create all required database tables
•	Load initial user data
5. Open the application: http://localhost/ClothesStore/login.php

User Access
Regular User Login
•	Email: o.ntebs@gmail.com
•	Password: 29ef52e7563626a96cea7f4b085c124
Admin Login
•	Email: admin@pasttimes.com
•	Password: admin123
•	Select “Login as Admin” on the login page
Features
Shopping Cart & Checkout
Users can select clothing items and add them to a shopping cart.
Option to:
•	Proceed to checkout
•	Continue shopping without losing cart items
•	Simple and streamlined checkout process.
Edit shopping cart
Users can:
•	Update item quantities
•	Remove items from the cart
•	Changes are applied instantly before checkout.
Seller Clothing Submission
•	Sellers can submit requests to sell clothing items on the platform
•	When submitting an item, the seller must provide:
•	A description of the clothing item
•	An image of the item
•	The clothing brand
•	Submitted items are reviewed by the administrator before being made available for purchase.
Administrator Communication
•	Administrators can communicate with both sellers and buyers through the platform
•	This communication helps:
•	Verify item details before approval
•	Ensure clothing items match their descriptions and images
•	Resolve buyer and seller inquiries
•	Maintain quality control of listed products
•	Ensure that purchased items are delivered correctly and in good condition,
Product Verification 
•	All seller-submitted items are subject to review and verification.
•	Administrators ensure:
•	Images accurately represent the product
•	Clothing items meet platform standards
•	Buyers receive the correct items in satisfactory condition.

Product Reviews
•	Customers can submit reviews on items
•	Help other users make informed purchasing decisions
•	Improve user engagement and trust.
Payment Gateway Integration
•	The system includes a payment gateway as part of the checkout process.
•	Users can securely proceed from the shopping cart to payment
•	This feature allows a seamless transition from cart to checkout
•	This enhances the realism of the e-commerce experience by simulating a complete purchase flow.
Easy Navigation
•	Clean and simple interface
•	Users can easily:
•	Browse products
•	Access their cart
•	Navigate between pages without confusion

User Management 
Admin
Administrators can:
•	Verify pending user registrations
•	Add new users
•	Update existing user details
•	Delete users from the system
User Profile
•	Regular users can view their profile information after logging in.
Resetting the Database
If needed, you can reset the system by re-running: http://localhost/ClothesStore/loadClothingStore.php
This will recreate all tables and reload the default data.  
