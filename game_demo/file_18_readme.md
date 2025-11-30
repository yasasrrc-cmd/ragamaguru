# Aviator Casino Game - Complete Project

## 📁 Project Structure
```
aviator-game/
├── config/
│   └── database.php
├── api/
│   ├── register.php
│   ├── login.php
│   ├── deposit.php
│   ├── withdraw.php
│   └── place-bet.php
├── admin/
│   ├── index.php
│   ├── users.php
│   └── transactions.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── game.js
├── index.php
├── register.php
├── login.php
├── logout.php
├── game.php
└── database.sql
```

## 🚀 Installation Steps

### Step 1: Create Database
1. Open phpMyAdmin or MySQL command line
2. Create a new database:
```sql
CREATE DATABASE aviator_game;
```

### Step 2: Import SQL File
1. Import the `database.sql` file into your newly created database
2. This will create all necessary tables and insert default admin user

### Step 3: Configure Database Connection
Edit `config/database.php` and update:
```php
private $host = "localhost";        // Your database host
private $db_name = "aviator_game";  // Database name
private $username = "root";          // Your database username
private $password = "";              // Your database password
```

### Step 4: Upload Files
Upload all files to your web server maintaining the folder structure

### Step 5: Set Permissions
```bash
chmod 755 aviator-game/
chmod 644 aviator-game/*.php
chmod 755 aviator-game/api/
chmod 644 aviator-game/api/*.php
```

### Step 6: Access the Application
Navigate to: `http://yourdomain.com/aviator-game/`

## 🔑 Default Login Credentials

**Admin Account:**
- Email: `admin@aviator.com`
- Password: `admin123`

**IMPORTANT:** Change the admin password immediately after first login!

## ✨ Features

- ✅ User Registration & Authentication
- ✅ Deposit & Withdrawal System
- ✅ Real-time Aviator Game
- ✅ Betting System with Auto Cash-Out
- ✅ Transaction History
- ✅ Admin Dashboard
- ✅ User Management
- ✅ Game Statistics
- ✅ Responsive Design
- ✅ Secure Password Hashing
- ✅ SQL Injection Protection

## 🎮 How to Play

1. **Register/Login:** Create an account or login
2. **Deposit:** Add funds to your wallet
3. **Place Bet:** Enter bet amount and click "Place Bet"
4. **Watch Multiplier:** The plane takes off and multiplier increases
5. **Cash Out:** Click "Cash Out" before the plane crashes
6. **Auto Cash-Out:** Set automatic cash-out at desired multiplier

## 🛠️ Technical Details

**Backend:**
- PHP 7.4+
- MySQL 5.7+
- PDO for database operations
- Session-based authentication

**Frontend:**
- HTML5
- CSS3 (Responsive Design)
- Vanilla JavaScript
- AJAX for API calls

## 🔒 Security Features

- Password hashing with bcrypt
- Prepared statements (SQL injection protection)
- Session management
- Input validation
- XSS protection with htmlspecialchars

## 📊 Database Tables

1. **users** - User accounts and balances
2. **transactions** - Deposit/withdrawal records
3. **bets** - Betting history
4. **game_settings** - Game configuration

## 🎨 Customization

### Change Colors
Edit `assets/css/style.css` to modify:
- Background gradient
- Button colors
- Game area colors

### Adjust Game Settings
Modify in database `game_settings` table:
- `min_bet` - Minimum bet amount
- `max_bet` - Maximum bet amount
- `max_multiplier` - Maximum crash point
- `game_duration` - Round duration

### Modify Crash Algorithm
Edit `generateCrashPoint()` function in `assets/js/game.js`

## 🐛 Troubleshooting

**Problem: Can't connect to database**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists

**Problem: Page shows blank/white screen**
- Enable error reporting in PHP
- Check PHP error logs
- Ensure all files are uploaded correctly

**Problem: Can't login**
- Clear browser cookies/cache
- Check if user exists in database
- Verify password is correct

**Problem: Game doesn't start**
- Check browser console for JavaScript errors
- Ensure all JS files are loaded
- Try different browser

## ⚠️ Legal Disclaimer

This software is provided for **educational purposes only**. 

Before launching any real-money gambling application:
- Consult with legal counsel about gambling laws in your jurisdiction
- Obtain necessary gambling licenses
- Implement KYC (Know Your Customer) verification
- Add responsible gambling features
- Include age verification
- Comply with all local regulations

## 📝 Future Enhancements

- [ ] Payment gateway integration (Stripe, PayPal)
- [ ] Provably fair algorithm
- [ ] Live chat system
- [ ] Email verification
- [ ] Password reset functionality
- [ ] Detailed betting statistics
- [ ] Leaderboard system
- [ ] Referral program
- [ ] Mobile app version
- [ ] Multi-currency support
- [ ] Two-factor authentication

## 💡 Support

For issues or questions:
1. Check the troubleshooting section
2. Review PHP error logs
3. Check browser console for errors
4. Verify all files are uploaded correctly

## 📄 License

This project is open-source and available for educational purposes.

---

**Made with ❤️ for learning PHP & MySQL**