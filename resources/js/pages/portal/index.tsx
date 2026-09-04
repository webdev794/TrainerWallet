import { Head, Link } from '@inertiajs/react';
import {
    CalendarClock,
    CheckCircle2,
    Flame,
    Receipt,
    Sparkles,
} from 'lucide-react';
import { type ComponentType } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ClientPortalLayout from '@/layouts/client-portal-layout';
import { formatDateTime } from '@/lib/format';
import * as portal from '@/routes/portal';
import * as trainers from '@/routes/trainers';

type Props = {
    linked: boolean;
    stats: {
        completed_total: number;
        completed_this_month: number;
        streak_weeks: number;
        open_invoices: number;
    };
    series: { month: string; sessions: number }[];
    nextSession: {
        scheduled_at: string;
        duration_minutes: number;
        trainer_name: string;
    } | null;
};

export default function PortalDashboard({
    linked,
    stats,
    series,
    nextSession,
}: Props) {
    const cards: {
        label: string;
        value: string;
        icon: ComponentType<{ className?: string }>;
    }[] = [
        {
            label: 'Sessions completed',
            value: String(stats.completed_total),
            icon: CheckCircle2,
        },
        {
            label: 'This month',
            value: String(stats.completed_this_month),
            icon: Sparkles,
        },
        {
            label: 'Week streak',
            value: String(stats.streak_weeks),
            icon: Flame,
        },
        {
            label: 'Invoices to pay',
            value: String(stats.open_invoices),
            icon: Receipt,
        },
    ];

    return (
        <ClientPortalLayout title="Your progress">
            <Head title="Dashboard" />

            {!linked && (
                <Card className="mb-6">
                    <CardContent className="text-muted-foreground py-6 text-center text-sm">
                        You&rsquo;re not connected to a trainer yet.{' '}
                        <Link
                            href={trainers.index().url}
                            className="text-foreground font-medium underline"
                        >
                            Find one
                        </Link>{' '}
                        and book your first session.
                    </CardContent>
                </Card>
            )}

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

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Completed sessions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-60 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={series}>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="var(--color-border)"
                                        vertical={false}
                                    />
                                    <XAxis
                                        dataKey="month"
                                        stroke="var(--color-muted-foreground)"
                                        fontSize={12}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        stroke="var(--color-muted-foreground)"
                                        fontSize={12}
                                        width={28}
                                    />
                                    <Tooltip
                                        cursor={{
                                            fill: 'var(--color-muted)',
                                            opacity: 0.4,
                                        }}
                                        contentStyle={{
                                            background: 'var(--color-card)',
                                            border: '1px solid var(--color-border)',
                                            borderRadius: 8,
                                            fontSize: 12,
                                        }}
                                    />
                                    <Bar
                                        dataKey="sessions"
                                        fill="var(--color-primary)"
                                        radius={[4, 4, 0, 0]}
                                        maxBarSize={44}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CalendarClock className="size-4" /> Next
                                session
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm">
                            {nextSession ? (
                                <>
                                    <p className="text-lg font-semibold">
                                        {formatDateTime(
                                            nextSession.scheduled_at,
                                        )}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {nextSession.duration_minutes} min with{' '}
                                        {nextSession.trainer_name}
                                    </p>
                                </>
                            ) : (
                                <p className="text-muted-foreground">
                                    Nothing scheduled.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="flex flex-col gap-2 py-6">
                            <Button asChild>
                                <Link href={portal.book().url}>
                                    Book a session
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={trainers.index().url}>
                                    Browse trainers
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </ClientPortalLayout>
    );
}
