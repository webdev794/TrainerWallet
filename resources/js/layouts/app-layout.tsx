import { Link, router, usePage } from '@inertiajs/react';
import {
    Boxes,
    CalendarCheck,
    CalendarDays,
    CreditCard,
    FileText,
    LayoutDashboard,
    LogOut,
    Menu,
    Repeat,
    Settings,
    Sparkles,
    Star,
    Users,
    Wallet,
} from 'lucide-react';
import { type ComponentType, type PropsWithChildren, useState } from 'react';

import { AppLogo } from '@/components/app-logo';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { useAuth } from '@/hooks/use-page-props';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';
import { dashboard, logout } from '@/routes';

type NavItem = {
    label: string;
    href: string;
    icon: ComponentType<{ className?: string }>;
};

const navItems: NavItem[] = [
    { label: 'Dashboard', href: dashboard.url(), icon: LayoutDashboard },
    { label: 'Clients', href: '/clients', icon: Users },
    { label: 'Sessions', href: '/sessions', icon: CalendarDays },
    { label: 'Bookings', href: '/bookings', icon: CalendarCheck },
    { label: 'Packages', href: '/packages', icon: Boxes },
    { label: 'Invoices', href: '/invoices', icon: FileText },
    { label: 'Recurring', href: '/recurring', icon: Repeat },
    { label: 'Payments', href: '/payments', icon: CreditCard },
    { label: 'Reports', href: '/reports', icon: Wallet },
    { label: 'Reviews', href: '/reviews', icon: Star },
    { label: 'Billing', href: '/billing', icon: Sparkles },
    { label: 'Settings', href: '/settings', icon: Settings },
];

function initials(name: string) {
    return name
        .split(' ')
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function logoutNow() {
    router.post(logout.url());
}

function SidebarNav({ onNavigate }: { onNavigate?: () => void }) {
    const { url } = usePage();

    return (
        <nav className="flex flex-1 flex-col gap-1 px-3 py-4">
            {navItems.map((item) => {
                const active =
                    item.href === dashboard.url()
                        ? url.startsWith('/dashboard')
                        : url.startsWith(item.href);

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        onClick={onNavigate}
                        className={cn(
                            'text-sidebar-muted hover:text-sidebar-foreground flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-white/5',
                            active &&
                                'bg-sidebar-accent/15 text-sidebar-foreground',
                        )}
                    >
                        <item.icon className="size-4" />
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );
}

export default function AppLayout({
    children,
    title,
    actions,
}: PropsWithChildren<{ title?: string; actions?: React.ReactNode }>) {
    useFlashToast();
    const { user, trainerProfile } = useAuth();
    const [mobileOpen, setMobileOpen] = useState(false);

    const verifyBanner = user && !user.email_verified_at;

    return (
        <div className="bg-muted/30 min-h-screen lg:grid lg:grid-cols-[16rem_1fr]">
            <aside className="bg-sidebar hidden flex-col lg:flex">
                <div className="flex h-16 items-center border-b border-white/10 px-5">
                    <Link href={dashboard.url()}>
                        <AppLogo className="text-sidebar-foreground" />
                    </Link>
                </div>
                <SidebarNav />
                <div className="space-y-2 border-t border-white/10 p-3">
                    <div className="text-sidebar-muted px-2 text-xs">
                        {trainerProfile?.business_name ?? user?.name}
                    </div>
                    <button
                        onClick={logoutNow}
                        className="text-sidebar-muted hover:text-sidebar-foreground flex w-full items-center gap-3 rounded-md px-2 py-2 text-sm font-medium transition-colors hover:bg-white/5"
                    >
                        <LogOut className="size-4" /> Log out
                    </button>
                </div>
            </aside>

            <div className="flex min-h-screen flex-col">
                <header className="bg-background sticky top-0 z-30 flex h-16 items-center gap-3 border-b px-4 lg:px-8">
                    <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="lg:hidden"
                            >
                                <Menu className="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="flex flex-col p-0">
                            <div className="flex h-16 items-center px-5">
                                <AppLogo className="text-sidebar-foreground" />
                            </div>
                            <SidebarNav
                                onNavigate={() => setMobileOpen(false)}
                            />
                            <div className="border-t border-white/10 p-3">
                                <button
                                    onClick={logoutNow}
                                    className="text-sidebar-muted hover:text-sidebar-foreground flex w-full items-center gap-3 rounded-md px-2 py-2 text-sm font-medium hover:bg-white/5"
                                >
                                    <LogOut className="size-4" /> Log out
                                </button>
                            </div>
                        </SheetContent>
                    </Sheet>

                    <div className="flex flex-1 items-center justify-between">
                        <h1 className="text-lg font-semibold">{title}</h1>
                        <div className="flex items-center gap-3">
                            {actions}
                            {trainerProfile && (
                                <Badge
                                    variant={
                                        trainerProfile.plan === 'pro'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {trainerProfile.plan === 'pro'
                                        ? 'Pro'
                                        : 'Free'}
                                </Badge>
                            )}
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button className="focus-visible:ring-ring flex items-center gap-2 rounded-full outline-none focus-visible:ring-2">
                                        <Avatar>
                                            {user?.avatar && (
                                                <AvatarImage
                                                    src={user.avatar}
                                                    alt={user.name}
                                                />
                                            )}
                                            <AvatarFallback>
                                                {user
                                                    ? initials(user.name)
                                                    : '?'}
                                            </AvatarFallback>
                                        </Avatar>
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="end"
                                    className="w-56"
                                >
                                    <DropdownMenuLabel>
                                        <div className="flex flex-col">
                                            <span>{user?.name}</span>
                                            <span className="text-muted-foreground text-xs font-normal">
                                                {user?.email}
                                            </span>
                                        </div>
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href="/settings">
                                            <Settings /> Settings
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        onSelect={(event) => {
                                            event.preventDefault();
                                            logoutNow();
                                        }}
                                    >
                                        <LogOut /> Log out
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </header>

                {verifyBanner && (
                    <div className="bg-warning/10 text-warning flex flex-wrap items-center justify-between gap-2 border-b px-4 py-2 text-sm lg:px-8">
                        <span>Verify your email to secure your account.</span>
                        <button
                            className="font-medium underline"
                            onClick={() =>
                                router.post('/email/verification-notification')
                            }
                        >
                            Resend link
                        </button>
                    </div>
                )}

                <main className="flex-1 p-4 lg:p-8">{children}</main>
            </div>

            <Toaster />
        </div>
    );
}
