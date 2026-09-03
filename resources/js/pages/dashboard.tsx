import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    CalendarDays,
    IndianRupee,
    Wallet,
} from 'lucide-react';
import { type ComponentType } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/format';
import * as invoices from '@/routes/invoices';

type Stats = {
    collected_this_month: number;
    outstanding: number;
    overdue_count: number;
    sessions_this_week: number;
};

type RecentInvoice = {
    id: number;
    number: string;
    client_name: string;
    status: string;
    status_label: string;
    total: string;
    currency: string;
};

const statusVariant: Record<
    string,
    'secondary' | 'success' | 'warning' | 'outline' | 'destructive'
> = {
    draft: 'outline',
    sent: 'secondary',
    viewed: 'secondary',
    partially_paid: 'warning',
    paid: 'success',
    overdue: 'destructive',
    void: 'outline',
};

export default function Dashboard({
    stats,
    recentInvoices,
    currency,
}: {
    stats: Stats;
    recentInvoices: RecentInvoice[];
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
        <AppLayout
            title="Dashboard"
            actions={
                <Button asChild size="sm">
                    <Link href={invoices.create().url}>New invoice</Link>
                </Button>
            }
        >
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
                <CardHeader>
                    <CardTitle>Recent invoices</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {recentInvoices.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 py-12 text-center">
                            <div className="bg-accent text-accent-foreground flex size-12 items-center justify-center rounded-full">
                                <ArrowRight className="size-6" />
                            </div>
                            <p className="text-muted-foreground max-w-sm text-sm">
                                Add a client, log a session, then raise your
                                first invoice.
                            </p>
                            <Button asChild size="sm">
                                <Link href="/clients">Go to clients</Link>
                            </Button>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Number</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recentInvoices.map((invoice) => (
                                    <TableRow
                                        key={invoice.id}
                                        className="cursor-pointer"
                                        onClick={() =>
                                            (window.location.href =
                                                invoices.show(invoice.id).url)
                                        }
                                    >
                                        <TableCell className="font-medium">
                                            {invoice.number}
                                        </TableCell>
                                        <TableCell>
                                            {invoice.client_name}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                invoice.total,
                                                invoice.currency || currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        invoice.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {invoice.status_label}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
