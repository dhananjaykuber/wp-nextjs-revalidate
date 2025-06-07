## NextJS Revalidate Plugin

A WordPress plugin that automatically triggers Next.js revalidation when content changes in WordPress.

### 🚀 Features

-   **Automatic Revalidation**: Triggers Next.js revalidation on content changes
-   **Multiple Content Types**: Supports posts, pages, terms, users, and media
-   **Secure Webhooks**: Uses secret tokens for secure communication
-   **Easy Configuration**: Simple admin interface for setup

### 🔧 Installation

-   Upload the plugin files to `/wp-content/plugins`
-   Activate the plugin through the WordPress admin

### ⚙️ Configuration

-   Go to Next Revalidate in WordPress admin
-   Enter your Next.js URL (e.g., https://nextjs-app.com)
-   Enter your Webhook Secret (must match your Next.js API)
-   Save settings

### Next.js API Route

Create an API route at `/app/api/revalidate/route.js` for App Router

```ts
// Next.js dependencies
import { revalidatePath } from 'next/cache';
import { NextRequest, NextResponse } from 'next/server';

const POST = async (request: NextRequest) => {
    try {
        const body = await request.json();
        const secret = request.headers.get('x-webhook-secret');

        const { contentType, contentId } = body;

        if (secret !== process.env.WORDPRESS_WEBHOOK_SECRET) {
            console.error(`Invalid webhook secret: ${secret}`);

            return NextResponse.json(
                { error: 'Invalid webhook secret' },
                { status: 401 }
            );
        }

        revalidatePath('/', 'layout');

        console.log(
            `Revalidation successful for content type: ${contentType} & ID: ${contentId}`
        );

        return NextResponse.json(
            {
                message: `Revalidation successful for content type: ${contentType} & ID: ${contentId}`,
            },
            { status: 200 }
        );
    } catch (error) {
        console.error(
            `Revalidation failed: ${
                error instanceof Error ? error.message : error
            }`
        );

        return NextResponse.json(
            { error: 'Revalidation failed' },
            { status: 500 }
        );
    }
};

export { POST };
```

### Environment Variables

Add to your .env.local file:

```env
WORDPRESS_WEBHOOK_SECRET=YOUR_WORDPRESS_WEBHOOK_SECRET
```

### Screenshot
![Screenshot 2025-06-08 at 12 39 23 AM](https://github.com/user-attachments/assets/8d6793e6-e3a1-40ed-9617-06dbfed0d119)
