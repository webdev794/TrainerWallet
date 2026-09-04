import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import ClientPortalLayout from '@/layouts/client-portal-layout';
import { formatDate, formatMoney } from '@/lib/format';
import * as portalReceipts from '@/routes/portal/receipts';

type Receipt = {
    id: number;
    invoice_number: string;
    amount: string;
    currency: string;
    method: string;
    reference: string | null;
    paid_at: string | null;
};

export default function PortalReceipts({
    receipts,
    linked = true,
}: {
    receipts: Receipt[];
    linked?: boolean;
}) {
    return (
        <ClientPortalLayout title="Receipts">
            <Head title="Receipts" />

            {!linked && (
                <Card className="mb-4">
                    <CardContent className="text-muted-foreground py-6 text-center text-sm">
                        Your account isn&rsquo;t linked to a trainer yet.
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardContent className="p-0">
                    {receipts.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            Receipts appear here once you&rsquo;ve paid an
                            invoice.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Method</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead className="text-right">
                                        Receipt
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {receipts.map((receipt) => (
                                    <TableRow key={receipt.id}>
                                        <TableCell className="text-muted-foreground">
                                            {receipt.paid_at
                                                ? formatDate(receipt.paid_at)
                                                : '—'}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {receipt.invoice_number}
                                        </TableCell>
                                        <TableCell>
                                            {receipt.method}
                                            {receipt.reference
                                                ? ` · ${receipt.reference}`
                                                : ''}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                receipt.amount,
                                                receipt.currency,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                            >
                                                <a
                                                    href={
                                                        portalReceipts.download(
                                                            receipt.id,
                                                        ).url
                                                    }
                                                >
                                                    <Download className="size-4" />{' '}
                                                    PDF
                                                </a>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </ClientPortalLayout>
    );
}
