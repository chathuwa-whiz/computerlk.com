# ECODEZ STORE - E-commerce Website

A modern, responsive e-commerce store built with PHP, Bootstrap 5, and MySQL.

## Features

- 🛒 Full e-commerce functionality
- 💰 Sri Lankan Rupee (LKR) Currency
- 📱 Fully responsive design
- 🎨 Modern, beautiful UI with animations
- ⚡ Fast loading performance
- 🛡️ 1 Year Warranty display
- 🚚 Islandwide delivery info
- 💳 Multiple payment methods
- ⭐ Customer reviews/testimonials
- 📧 Newsletter subscription

## Project Structure

```
ecodestore/
├── assets/
│   ├── css/
│   │   └── style.css          # Custom CSS
│   ├── js/
│   │   └── main.js            # JavaScript functionality
│   └── img/                   # Images folder
├── includes/
│   ├── header.php             # Header & Navigation
│   └── footer.php             # Footer
├── config.php                 # Database configuration
├── index.php                  # Homepage
├── .htaccess                  # URL rewrite rules
└── README.md                  # This file
```

## Tech Stack

- **Backend:** PHP 8+
- **Frontend:** Bootstrap 5.3 + Custom CSS
- **Database:** MySQL
- **Icons:** Bootstrap Icons, Font Awesome
- **Fonts:** Google Fonts (Poppins)

## Setup Instructions

### 1. Install XAMPP
- Download and install XAMPP from https://www.apachefriends.org/
- Start Apache and MySQL services

### 2. Database Setup
- Open phpMyAdmin at http://localhost/phpmyadmin
- Create a new database named `ecodestore`
- Import the SQL structure (will be provided)

### 3. Configure
- Open `config.php`
- Update database credentials if needed:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  define('DB_NAME', 'ecodestore');
  ```

### 4. Access the Website
- Open your browser
- Navigate to: http://localhost/ecodestore

## Features Overview

### Homepage Sections
1. **Hero Section** - Featured product with CTA
2. **Features Bar** - Delivery, warranty, payments
3. **Shop by Category** - 8 product categories
4. **Featured Products** - 4 best-selling products
5. **Why Choose Us** - 4 key benefits
6. **Testimonials** - 3 customer reviews
7. **Newsletter** - Email subscription

### Design Highlights
- Gradient backgrounds
- Smooth animations
- Hover effects
- Mobile responsive
- Fast loading
- Modern color scheme

## Customization

### Change Colors
Edit `assets/css/style.css`:
```css
:root {
    --primary: #2563eb;      /* Primary color */
    --secondary: #1e40af;    /* Secondary color */
    --accent: #f59e0b;       /* Accent color */
}
```

### Add Products
Place product images in `assets/img/`
Update product data in `index.php` or create database entries

### Change Content
Edit the following files:
- `includes/header.php` - Navigation and branding
- `includes/footer.php` - Footer information
- `index.php` - Homepage content

## Performance Tips

- Enable Gzip compression in `.htaccess`
- Optimize images before uploading
- Use browser caching
- Minify CSS/JS files
- Use CDN for Bootstrap resources

## Browser Compatibility

- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)
- ✅ Mobile browsers

## Future Enhancements

- [ ] Admin panel
- [ ] Shopping cart system
- [ ] User authentication
- [ ] Order management
- [ ] Payment gateway integration
- [ ] Product search & filters
- [ ] Wishlist feature

## Support

For issues or questions:
- Email: info@ecodestore.lk
- Phone: +94 71 234 5678

## Credits

Built by: Ecodez Digital Solution
Technologies: PHP, Bootstrap 5, MySQL

---

**© 2024 Ecodez Store. All Rights Reserved.**