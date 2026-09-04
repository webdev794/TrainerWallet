import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

import { AppLogo } from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/hooks/use-page-props';
import { dashboard, login, register } from '@/routes';
import * as trainers from '@/routes/trainers';

export default function MarketingLayout({ children }: PropsWithChildren) {
    const { user } = useAuth();

    return (
        <div className="bg-background flex min-h-screen flex-col">
            <header className="bg-background/80 sticky top-0 z-40 border-b backdrop-blur">
                <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
                    <Link href="/">
                        <AppLogo />
                    </Link>

                    <nav className="text-muted-foreground hidden items-center gap-8 text-sm font-medium md:flex">
                        <Link
                            href={trainers.index().url}
                            className="hover:text-foreground"
                        >
                            Find a trainer
                        </Link>
                        <a href="/#features" className="hover:text-foreground">
                            Features
                        </a>
                        <a href="/#pricing" className="hover:text-foreground">
                            Pricing
                        </a>
                    </nav>

                    <div className="flex items-center gap-3">
                        {user ? (
                            <Button asChild size="sm">
                                <Link href={dashboard.url()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href={login.url()}>Log in</Link>
                                </Button>
                                <Button asChild size="sm">
                                    <Link href={register.url()}>
                                        Start free trial
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="bg-muted/30 border-t">
                <div className="text-muted-foreground mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-10 text-sm md:flex-row md:items-center md:justify-between">
                    <div className="flex items-center gap-2">
                        <AppLogo />
                    </div>
                    <p>
                        &copy; {new Date().getFullYear()} CoachPay. Payments for
                        fitness professionals.
                    </p>
                </div>
            </footer>
        </div>
    );
}
