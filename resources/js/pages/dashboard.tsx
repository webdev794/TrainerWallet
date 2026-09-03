import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    CalendarDays,
    IndianRupee,
    Wallet,
} from 'lucide-react';
import { type ComponentType } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

type Stats = {
    collected_this_month: number;
    outstanding: number;
    overdue_count: number;
    sessions_this_week: number;
};

function formatMoney(amount: number, currency: string) {
    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency,
            maximumFractionDigits: 0,
        }).format(amount);
    } catch {
        return `${currency} ${amount}`;
    }
}

export default function Dashboard({
    stats,
    currency,
}: {
    stats: Stats;
    currency: string;
}) {
    const cards: {
        label: string;
        value: string;
        icon: ComponentType<{ className?: string }>;
    }[] = [
        {
            label: 'Collected this month',
            value: formatMoney(stats.collected_this_month, currency),
            icon: IndianRupee,
        },
        {
            label: 'Outstanding',
            value: formatMoney(stats.outstanding, currency),
            icon: Wallet,
        },
        {
            label: 'Overdue invoices',
            value: String(stats.overdue_count),
            icon: AlertTriangle,
        },
        {
            label: 'Sessions this week',
            value: String(stats.sessions_this_week),
            icon: CalendarDays,
        },
    ];

    return (
        <AppLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {cards.map((card) => (
                    <Card key={card.label}>
                        <CardHeader className="flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">
                                {card.label}
                            </CardTitle>
                            <card.icon className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{card.value}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Card className="mt-6">
                <CardContent className="flex flex-col items-center gap-3 py-16 text-center">
                    <div className="bg-accent text-accent-foreground flex size-12 items-center justify-center rounded-full">
                        <ArrowRight className="size-6" />
                    </div>
                    <h2 className="text-lg font-semibold">
                        Add your first client to get started
                    </h2>
                    <p className="text-muted-foreground max-w-sm text-sm">
                        Once you have a client and a logged session, you can
                        raise an invoice and start collecting payments.
                    </p>
                    <Button asChild>
                        <Link href="/clients">Go to clients</Link>
                    </Button>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
