# KOH 2027 Campaign Website

A full-stack Laravel + Vue 3 web application for Kadri Obafemi Hamzat's political campaign.

## Overview

This is a modern web platform featuring a public-facing website and a comprehensive admin content management system (CMS). Built with Laravel 11, Vue 3, Inertia.js, and Tailwind CSS.

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Vue 3 (Composition API)
- **SSR/SPA Framework**: Inertia.js
- **UI Framework**: Tailwind CSS
- **Build Tool**: Vite
- **Database**: MySQL
- **Rich Text Editor**: Tiptap (MIT licensed)
- **Authentication**: Laravel built-in auth with admin roles

## Features

### Public Website
- Hero slider with featured images
- About page with dynamic content
- Gallery with image categories and filtering
- News/Articles section
- Events management and RSVP
- Platform/Pillars section highlighting key initiatives
- Merchandise store
- Downloadable materials
- Contact form with admin notifications
- Newsletter subscription
- Volunteer registration

### Admin Panel
- Role-based access control (Admin, Editor)
- Dashboard with analytics
- Content Management
  - Hero slides management
  - Gallery (multi-image upload with drag-and-drop)
  - News articles with rich text editor
  - Events management
  - Platform pillars
  - Merchandise with multiple images
  - Testimonials
  - Materials/Documents
- CRM Features
  - Contact tracking
  - Volunteer management
  - Newsletter subscriber management
  - Activity logs
  - Bulk email campaigns
  - CSV export capabilities
- User management with role assignment
- Analytics and reporting

## Installation

### Prerequisites
- PHP 8.3+
- MySQL 8.0+
- Node.js 18+
- Composer

### Setup

```bash
# Clone the repository
git clone <repo-url>
cd koh2027

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations and seed database
php artisan migrate --seed

# Build assets
npm run build

# Start dev server (in separate terminal)
npm run dev

# Start Laravel server
php artisan serve
```

### Accessing the Application
- **Public Site**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin/login
- **Default Admin**: Email: `admin@example.com` | Password: `password`

## Project Structure

```
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/          # Admin CMS controllers
│   │   └── ...             # Public-facing controllers
│   └── Models/             # Eloquent models
├── resources/
│   ├── js/
│   │   ├── Components/     # Reusable Vue components
│   │   ├── Layouts/        # Layout wrappers
│   │   ├── Pages/          # Page components
│   │   │   ├── Admin/      # Admin pages
│   │   │   └── ...         # Public pages
│   │   └── app.js          # Vue entry point
│   └── css/
├── routes/
│   ├── web.php             # All routes
│   └── api.php
├── database/
│   ├── migrations/         # Schema migrations
│   └── seeders/            # Database seeders
└── public/storage/         # User uploaded files
```

## Development

### Running Development Server
```bash
# Terminal 1: Run Laravel dev server
php artisan serve

# Terminal 2: Run Vite dev server
npm run dev
```

### Building for Production
```bash
npm run build
```

### Database Commands
```bash
# Run migrations
php artisan migrate

# Seed demo data
php artisan db:seed

# Reset database
php artisan migrate:fresh --seed
```

## Key Components

### Editor Component
Rich text editor (Tiptap) with formatting toolbar:
- Text formatting: Bold, Italic, Underline, Strikethrough
- Headings (H1-H3)
- Lists (Bullet, Ordered)
- Blockquotes and code blocks
- Undo/Redo

### Gallery System
- Multi-image upload with drag-and-drop
- Category-based organization
- Edit and delete functionality
- Image storage in public disk

### News System
- Create/edit articles with rich text content
- Featured image support
- Category organization
- Publish/archive functionality

### Form Validation
- Server-side validation with Laravel
- Client-side error handling with Inertia
- User-friendly error messages

## Security

- CSRF protection on all forms
- Authorization gates for admin actions
- Role-based access control
- SQL injection prevention via Eloquent ORM
- XSS protection via Vue templating
- Secure file upload handling

## Contributing

When adding new features:
1. Create database migration if needed
2. Add corresponding model and controller
3. Create Vue components for UI
4. Add routes in `routes/web.php`
5. Test thoroughly in both dev and production modes

## License

This project is proprietary software for the KOH 2027 campaign.

## Support

For issues or questions, contact the development team.
