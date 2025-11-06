# AmarEvents

**AmarEvents** is a modern event management and booking platform designed to make organizing, promoting, and managing events effortless. Whether itâ€™s a wedding, concert, corporate event, or private celebration â€” AmarEvents helps you handle everything from start to finish.

ðŸŒ **Live Website:** [https://amarevents.zone.id](https://amarevents.zone.id)

---

## ðŸš€ Features

- ðŸŽŸï¸ Easy event creation & ticket booking  
- ðŸ—“ï¸ Smart scheduling and live updates  
- ðŸ’³ Secure payments and confirmation flow  
- ðŸ“Š Organizer dashboard with analytics  
- ðŸ“¸ Event gallery & highlights  
- ðŸ“± Fully responsive mobileâ€‘friendly UI  
- â˜ï¸ Hosted on **AlwaysData** â€” reliable, free, and easy to deploy

---

## ðŸ§° Tech Stack

- **Frontend:** HTML, CSS, JavaScript (Bootstrap)  
- **Backend:** PHP  
- **Database:** MySQL  
- **Hosting:** AlwaysData (Free plan)

---

## ðŸ§© Setup & Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/harunabdullahrakin/Amar-Events.git
   cd Amar-Events
   ```

2. **Copy and configure environment file**
   ```bash
   cp config/config.sample.php config/config.php
   ```
   Then edit `config.php` with your database and mail credentials.

3. **Import database**
   ```bash
   mysql -u username -p database_name < database/schema.sql
   ```

4. **Run locally (optional)**
   If youâ€™re using PHP locally:
   ```bash
   php -S localhost:8000
   ```
   Then visit [http://localhost:8000](http://localhost:8000)

5. **Deploy on AlwaysData**
   - Upload your project files to the `/www/` directory in your AlwaysData account.  
   - Connect your custom domain (e.g., `amarevents.zone.id`) in the dashboard.  
   - Enjoy fast, free, and reliable hosting for your PHP website.

---

## ðŸ§¾ Project Structure

```
/www/             â†’ Web root directory  
/config/          â†’ Configuration files (database, mail, etc.)  
/public/          â†’ Assets (CSS, JS, images)  
/src/             â†’ Backend PHP source code  
```

---

## ðŸ” Security Tips

- Never commit credentials or passwords.  
- Add these to `.gitignore`:
  ```
  /config/config.php
  /.env
  /vendor/
  /node_modules/
  ```
- Enable **Secret Scanning** and **Push Protection** in GitHub under *Settings â†’ Code security and analysis*.

---

## ðŸ“œ License

This project is licensed under the **MIT License** â€” see the `LICENSE` file for details.

---

## ðŸ’¬ Contact

Maintained by **Harun Abdullah Rakin**  
GitHub â†’ [@harunabdullahrakin](https://github.com/harunabdullahrakin)  
Website â†’ [https://amarevents.zone.id](https://amarevents.zone.id)

---

> To get the best experience use alwaysdata.com. 
