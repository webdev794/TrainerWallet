import { Link, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { type PropsWithChildren } from 'react';

import { AppLogo } from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';

const links = [
    { label: 'Invoices', href: '/portal' },
    { label: 'Sessions', href: '/portal/sessions' },
    { label: 'Receipts', href: '/portal/receipts' },
];

export default function ClientPortalLayout({
    children,
    title,
}: PropsWithChildren<{ title?: string }>) {
    useFlashToast();
    const { url } = usePage();

    return (
        <div className="bg-muted/30 flex min-h-screen flex-col">
            <header className="bg-background border-b">
                <div className="mx-auto flex h-16 w-full max-w-4xl items-center justify-between px-4">
                    <AppLogo />
                    <nav className="text-muted-foreground flex items-center gap-6 text-sm font-medium">
                        {links.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className={cn(
                                    'hover:text-foreground',
                                    url.startsWith(link.href) &&
                                        'text-foreground',
                                )}
                            >
                                {link.label}
                            </Link>
                        ))}
                    </nav>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.post(logout.url())}
                    >
                        <LogOut className="size-4" /> Log out
                    </Button>
                </div>
            </header>

            <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-8">
                {title && (
                    <h1 className="mb-6 text-2xl font-semibold">{title}</h1>
                )}
                {children}
            </main>

            <Toaster />
        </div>
    );
}
