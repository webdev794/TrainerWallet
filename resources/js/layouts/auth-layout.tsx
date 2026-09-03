import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

import { AppLogo } from '@/components/app-logo';
import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/hooks/use-flash-toast';

export default function AuthLayout({
    children,
    title,
    description,
}: PropsWithChildren<{ title: string; description?: string }>) {
    useFlashToast();

    return (
        <div className="grid min-h-screen lg:grid-cols-2">
            <div className="bg-sidebar text-sidebar-foreground relative hidden flex-col justify-between p-10 lg:flex">
                <Link href="/">
                    <AppLogo className="text-sidebar-foreground" />
                </Link>
                <div className="space-y-4">
                    <p className="text-2xl leading-snug font-medium">
                        &ldquo;I send an invoice the moment a session ends and
                        the money lands before the next client shows up.&rdquo;
                    </p>
                    <p className="text-sidebar-muted text-sm">
                        Priya S. &middot; Strength coach
                    </p>
                </div>
                <p className="text-sidebar-muted text-sm">
                    Invoicing, reminders, and payouts on autopilot.
                </p>
            </div>

            <div className="flex items-center justify-center p-6">
                <div className="w-full max-w-sm space-y-6">
                    <div className="flex flex-col items-center gap-2 lg:hidden">
                        <AppLogo />
                    </div>
                    <div className="space-y-2 text-center">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-muted-foreground text-sm">
                                {description}
                            </p>
                        )}
                    </div>
                    {children}
                </div>
            </div>

            <Toaster />
        </div>
    );
}
