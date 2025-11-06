# AmarEvents

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.2-blue)](#)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)](#)
[![AlwaysData](https://img.shields.io/badge/Hosted%20on-AlwaysData-lightgrey)](https://www.alwaysdata.com)
[![Website](https://img.shields.io/badge/Live-Website-blueviolet)](https://amarevents.zone.id)

**AmarEvents** is a modern event management and booking platform that helps you create, manage, and promote events effortlessly. Perfect for weddings, concerts, corporate events, or private celebrations.

🌐 **Live Website:** [https://amarevents.zone.id](https://amarevents.zone.id)

---

## 🔥 Features

-  Easy event creation & ticket booking  
-  Smart scheduling and live updates  
-  Secure payments and confirmation flow  
-  Organizer dashboard with analytics  
- Event gallery & highlights  
- Fully responsive mobile-friendly UI  
-  Hosted on **AlwaysData** â€” reliable, free, and easy to deploy  

---

## 🗺️ Screenshots

**Homepage**  
![Homepage](https://via.placeholder.com/800x400?text=Homepage+Screenshot)  

**Organizer Dashboard**  
![Dashboard](https://via.placeholder.com/800x400?text=Dashboard+Screenshot)  

**Ticket Booking**  
![Ticket](https://via.placeholder.com/800x400?text=Ticket+Booking+Screenshot)  

---

## 🚥 Tech Stack

- **Frontend:** HTML, CSS, JavaScript (Bootstrap)  
- **Backend:** PHP  
- **Database:** MySQL  
- **Hosting:** AlwaysData (Free plan)  

---

## 📙 Project Structure

```
/www/             â†’ Web-root directory  
/config/          â†’ Configuration files (database, mail, etc.)  
/public/          â†’ Front-end assets (CSS, JS, images)  
/src/             â†’ Backend PHP source code  
/database/        â†’ SQL schema and initial data  
```

---

## 💡›  Setup & Installation

1. **Clone the repository**  
   ```bash
   git clone https://github.com/harunabdullahrakin/Amar-Events.git
   cd Amar-Events
   ```  
2. **Copy and configure environment file**  
   ```bash
   cp config/config.sample.php config/config.php
   ```  
   Then edit `config.php` with your database credentials, mail settings, etc.  
3. **Import the database schema**  
   ```bash
   mysql -u username -p database_name < database/schema.sql
   ```  
4. **Run locally (optional)**  
   ```bash
   php -S localhost:8000
   ```  
   Visit [http://localhost:8000](http://localhost:8000)  
5. **Deploy on AlwaysData**  
   - Upload files to `/www/`  
   - Connect your custom domain  
   - Enjoy free, reliable hosting  

---

## 💻 Security Tips

- Never commit credentials or passwords.  
- Add to `.gitignore`:  
  ```
  /config/config.php
  /.env
  /vendor/
  /node_modules/
  ```  
- Enable **Secret Scanning** and **Push Protection** in GitHub.

---

## 📑 License

MIT License â€” see the `LICENSE` file.  

---

## ✉️ Contact

**Harun Abdullah Rakin**  
GitHub â†’ [@harunabdullahrakin](https://github.com/harunabdullahrakin)  
Website â†’ [https://amarevents.zone.id](https://amarevents.zone.id)  

---

## 🗓️ Tags

`PHP` `MySQL` `Bootstrap` `Event Management` `Ticket Booking` `AlwaysData` `Free Hosting` `Responsive UI` `Analytics Dashboard`
