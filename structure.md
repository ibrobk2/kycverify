# Project Structure

lildone/
├── assets/
│   ├── css/
│   │   ├── main.css
│   │   ├── animations.css
│   │   └── responsive.css
│   ├── js/
│   │   ├── main.js
│   │   ├── animations.js
│   │   └── form-validation.js
│   ├── images/
│   │   ├── logo.png
│   │   ├── hero-bg.jpg
│   │   └── icons/
│   └── fonts/
│       └── (font files)
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── config.php
│   ├── db.php
│   └── functions.php
├── classes/
│   ├── Database.php
│   ├── User.php
│   ├── Product.php
│   └── Contact.php
├── admin/
│   ├── dashboard.php
│   ├── products/
│   │   ├── index.php
│   │   └── edit.php
│   └── users/
│       ├── index.php
│       └── edit.php
├── src/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── ProductController.php
│   │   └── ContactController.php
│   └── models/
│       ├── UserModel.php
│       ├── ProductModel.php
│       └── ContactModel.php
├── public/
│   ├── index.php
│   ├── about.php
│   ├── products.php
│   ├── contact.php
│   └── auth/
│       ├── login.php
│       └── register.php
└── vendor/
    └── (composer dependencies)