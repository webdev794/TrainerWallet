import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

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
import * as reportsPayments from '@/routes/reports/payments';

type Point = { month: string; gross: number; net: number };
type ClientRow = {
    client_name: string;
    open_invoices: number;
    balance: number;
};

export default function ReportsIndex({
    series,
    byClient,
    totals,
    currency,
}: {
    series: Point[];
    byClient: ClientRow[];
    totals: { collected_ytd: number; net_ytd: number };
    currency: string;
}) {
    return (
        <AppLayout
            title="Reports"
            actions={
                <Button asChild variant="outline" size="sm">
                    <a href={reportsPayments.csv.url()}>
                        <Download className="size-4" /> Export payments CSV
                    </a>
                </Button>
            }
        >
            <Head title="Reports" />

            <div className="grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-muted-foreground text-sm font-medium">
                            Collected year to date
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">
                            {formatMoney(totals.collected_ytd, currency)}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-muted-foreground text-sm font-medium">
                            Net after fees (YTD)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">
                            {formatMoney(totals.net_ytd, currency)}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-4">
                <CardHeader>
                    <CardTitle>Revenue — last 6 months</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-72 w-full">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={series}>
                                <defs>
                                    <linearGradient
                                        id="gross"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="5%"
                                            stopColor="var(--color-primary)"
                                            stopOpacity={0.3}
                                        />
                                        <stop
                                            offset="95%"
                                            stopColor="var(--color-primary)"
                                            stopOpacity={0}
                                        />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    stroke="var(--color-border)"
                                />
                                <XAxis
                                    dataKey="month"
                                    stroke="var(--color-muted-foreground)"
                                    fontSize={12}
                                />
                                <YAxis
                                    stroke="var(--color-muted-foreground)"
                                    fontSize={12}
                                    width={70}
                                />
                                <Tooltip
                                    formatter={(value) =>
                                        formatMoney(Number(value), currency)
                                    }
                                    contentStyle={{
                                        background: 'var(--color-card)',
                                        border: '1px solid var(--color-border)',
                                        borderRadius: 8,
                                        fontSize: 12,
                                    }}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="gross"
                                    stroke="var(--color-primary)"
                                    fill="url(#gross)"
                                    name="Gross"
                                />
                                <Area
                                    type="monotone"
                                    dataKey="net"
                                    stroke="var(--color-muted-foreground)"
                                    fill="transparent"
                                    name="Net"
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardHeader>
                    <CardTitle>Outstanding by client</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {byClient.length === 0 ? (
                        <p className="text-muted-foreground p-8 text-center text-sm">
                            Nothing outstanding — you&rsquo;re all caught up.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Open invoices</TableHead>
                                    <TableHead className="text-right">
                                        Balance
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {byClient.map((row) => (
                                    <TableRow key={row.client_name}>
                                        <TableCell className="font-medium">
                                            {row.client_name}
                                        </TableCell>
                                        <TableCell>
                                            {row.open_invoices}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatMoney(row.balance, currency)}
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
