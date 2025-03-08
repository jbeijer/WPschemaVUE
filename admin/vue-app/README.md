# WPschemaVUE Admin App

This is the admin interface for the WPschemaVUE WordPress plugin. It's built with Vue 3, Vite, Pinia, and Vue Router.

## Project Structure

```
admin/vue-app/
├── dist/               # Built files (generated)
├── node_modules/       # Dependencies (generated)
├── public/             # Static assets
├── scripts/            # Build scripts
│   └── copy-dist.js    # Script to copy built files to plugin directory
├── src/                # Source code
│   ├── assets/         # Assets (images, fonts, etc.)
│   ├── components/     # Vue components
│   ├── router/         # Vue Router configuration
│   ├── stores/         # Pinia stores
│   ├── views/          # Vue views/pages
│   ├── App.vue         # Root component
│   └── main.js         # Entry point
├── .gitignore          # Git ignore file
├── index.html          # HTML template
├── package.json        # Project configuration
└── vite.config.js      # Vite configuration
```

## Development

### Prerequisites

- Node.js (v14 or later)
- npm or yarn

### Setup

1. Install dependencies:

```bash
npm install
```

2. Start the development server:

```bash
npm run dev
```

This will start a development server with hot-reload enabled. Note that the development server runs independently from WordPress, so API calls will not work unless you have the WordPress site running locally.

### Building for Production

1. Build the app:

```bash
npm run build
```

This will create a `dist` directory with the built files.

2. Copy the built files to the plugin directory:

```bash
npm run copy-dist
```

This will copy the built files to the `admin/dist` directory, which is where the WordPress plugin expects them to be.

## WordPress Integration

The admin app is integrated with WordPress through the `admin/class-admin.php` file. This file registers the necessary scripts and styles, and passes data to the Vue app through the `wpScheduleData` global variable.

### Data Passed to the Vue App

The following data is passed to the Vue app:

- `nonce`: A WordPress nonce for API requests
- `rest_url`: The URL to the WordPress REST API
- `admin_url`: The URL to the WordPress admin
- `plugin_url`: The URL to the plugin directory
- `current_user`: Information about the current user
- `pages`: Information about the admin pages

## API Endpoints

The Vue app communicates with the WordPress backend through the REST API. The API endpoints are defined in the `includes/class-api.php` file.

For more information about the API endpoints, see the `APIendpoints.md` file in the plugin root directory.
