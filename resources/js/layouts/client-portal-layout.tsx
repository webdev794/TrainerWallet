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
    { label: 'Dashboard', href: '/portal', exact: true },
    { label: 'Book', href: '/portal/book' },
    { label: 'Bookings', href: '/portal/bookings' },
    { label: 'Invoices', href: '/portal/invoices' },
    { label: 'Sessions', href: '/portal/sessions' },
    { label: 'Receipts', href: '/portal/receipts' },
    { label: 'Reviews', href: '/portal/reviews' },
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
                <div className="mx-auto flex h-16 w-full max-w-5xl items-center justify-between gap-4 px-4">
                    <Link href="/portal">
                        <AppLogo />
                    </Link>
                    <nav className="text-muted-foreground -mx-2 flex flex-1 items-center gap-4 overflow-x-auto px-2 text-sm font-medium">
                        {links.map((link) => {
                            const active = link.exact
                                ? url === link.href ||
                                  url.split('?')[0] === link.href
                                : url.startsWith(link.href);

                            return (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className={cn(
                                        'hover:text-foreground whitespace-nowrap',
                                        active && 'text-foreground',
                                    )}
                                >
                                    {link.label}
                                </Link>
                            );
                        })}
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

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
                {title && (
                    <h1 className="mb-6 text-2xl font-semibold">{title}</h1>
                )}
                {children}
            </main>

            <Toaster />
        </div>
    );
}
