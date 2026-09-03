import { Head, router, useForm } from '@inertiajs/react';
import { Pause, Play, Plus, Trash2, Zap } from 'lucide-react';
import { type FormEvent, useState } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { InputError } from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatMoney } from '@/lib/format';
import * as recurring from '@/routes/recurring';

type Schedule = {
    id: number;
    client_name: string;
    interval: string;
    amount: number;
    auto_send: boolean;
    next_run_at: string;
    last_generated_at: string | null;
    status: string;
};

type ClientOption = { id: number; name: string; default_rate: string | null };
type Item = { description: string; quantity: string; unit_amount: string };

function CreateDialog({
    open,
    onOpenChange,
    clients,
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    clients: ClientOption[];
}) {
    const form = useForm<{
        client_id: string;
        interval: string;
        due_days: string;
        next_run_at: string;
        auto_send: boolean;
        tax_rate: string;
        notes: string;
        allowed_methods: string[];
        items: Item[];
    }>({
        client_id: '',
        interval: 'month',
        due_days: '7',
        next_run_at: new Date().toISOString().slice(0, 10),
        auto_send: true,
        tax_rate: '0',
        notes: '',
        allowed_methods: ['upi_manual', 'stripe'],
        items: [
            { description: 'Monthly coaching', quantity: '1', unit_amount: '' },
        ],
    });

    const setItem = (i: number, patch: Partial<Item>) =>
        form.setData(
            'items',
            form.data.items.map((item, idx) =>
                idx === i ? { ...item, ...patch } : item,
            ),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(recurring.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New recurring schedule</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="client_id">Client</Label>
                        <Select
                            id="client_id"
                            value={form.data.client_id}
                            onChange={(e) =>
                                form.setData('client_id', e.target.value)
                            }
                        >
                            <option value="">Choose a client…</option>
                            {clients.map((client) => (
                                <option key={client.id} value={client.id}>
                                    {client.name}
                                </option>
                            ))}
                        </Select>
                        <InputError message={form.errors.client_id} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="interval">Every</Label>
                            <Select
                                id="interval"
                                value={form.data.interval}
                                onChange={(e) =>
                                    form.setData('interval', e.target.value)
                                }
                            >
                                <option value="week">Week</option>
                                <option value="month">Month</option>
                                <option value="quarter">Quarter</option>
                                <option value="year">Year</option>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="next_run_at">First run</Label>
                            <Input
                                id="next_run_at"
                                type="date"
                                value={form.data.next_run_at}
                                onChange={(e) =>
                                    form.setData('next_run_at', e.target.value)
                                }
                            />
                            <InputError message={form.errors.next_run_at} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="due_days">Due in (days)</Label>
                            <Input
                                id="due_days"
                                type="number"
                                min="0"
                                value={form.data.due_days}
                                onChange={(e) =>
                                    form.setData('due_days', e.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label>Line items</Label>
                        {form.data.items.map((item, i) => (
                            <div key={i} className="grid grid-cols-12 gap-2">
                                <Input
                                    className="col-span-7"
                                    placeholder="Description"
                                    value={item.description}
                                    onChange={(e) =>
                                        setItem(i, {
                                            description: e.target.value,
                                        })
                                    }
                                />
                                <Input
                                    className="col-span-2"
                                    type="number"
                                    min="0"
                                    value={item.quantity}
                                    onChange={(e) =>
                                        setItem(i, { quantity: e.target.value })
                                    }
                                />
                                <Input
                                    className="col-span-3"
                                    type="number"
                                    min="0"
                                    placeholder="Amount"
                                    value={item.unit_amount}
                                    onChange={(e) =>
                                        setItem(i, {
                                            unit_amount: e.target.value,
                                        })
                                    }
                                />
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                form.setData('items', [
                                    ...form.data.items,
                                    {
                                        description: '',
                                        quantity: '1',
                                        unit_amount: '',
                                    },
                                ])
                            }
                        >
                            <Plus className="size-4" /> Row
                        </Button>
                        <InputError message={form.errors.items} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.auto_send}
                            onChange={(e) =>
                                form.setData('auto_send', e.target.checked)
                            }
                        />
                        Send automatically when generated
                    </label>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Create schedule
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function RecurringIndex({
    schedules,
    clients,
    currency,
}: {
    schedules: Schedule[];
    clients: ClientOption[];
    currency: string;
}) {
    const [open, setOpen] = useState(false);

    return (
        <AppLayout
            title="Recurring invoices"
            actions={
                <Button
                    size="sm"
                    onClick={() => setOpen(true)}
                    disabled={clients.length === 0}
                >
                    <Plus className="size-4" /> New schedule
                </Button>
            }
        >
            <Head title="Recurring invoices" />

            <Card>
                <CardContent className="p-0">
                    {schedules.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            Set up a schedule to bill monthly clients
                            automatically.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Every</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Next run</TableHead>
                                    <TableHead>Auto-send</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {schedules.map((schedule) => (
                                    <TableRow key={schedule.id}>
                                        <TableCell className="font-medium">
                                            {schedule.client_name}
                                        </TableCell>
                                        <TableCell className="capitalize">
                                            {schedule.interval}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                schedule.amount,
                                                currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(schedule.next_run_at)}
                                        </TableCell>
                                        <TableCell>
                                            {schedule.auto_send ? 'Yes' : 'No'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    schedule.status === 'active'
                                                        ? 'success'
                                                        : 'outline'
                                                }
                                            >
                                                {schedule.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    title="Generate now"
                                                    onClick={() =>
                                                        router.post(
                                                            recurring.run(
                                                                schedule.id,
                                                            ).url,
                                                        )
                                                    }
                                                >
                                                    <Zap className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    title={
                                                        schedule.status ===
                                                        'active'
                                                            ? 'Pause'
                                                            : 'Resume'
                                                    }
                                                    onClick={() =>
                                                        router.put(
                                                            recurring.update(
                                                                schedule.id,
                                                            ).url,
                                                            {
                                                                status:
                                                                    schedule.status ===
                                                                    'active'
                                                                        ? 'paused'
                                                                        : 'active',
                                                            },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    {schedule.status ===
                                                    'active' ? (
                                                        <Pause className="size-4" />
                                                    ) : (
                                                        <Play className="size-4" />
                                                    )}
                                                </Button>
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    }
                                                    title="Delete schedule?"
                                                    description="No more invoices will be generated for this client."
                                                    confirmLabel="Delete"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            recurring.destroy(
                                                                schedule.id,
                                                            ).url,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>

            <CreateDialog
                open={open}
                onOpenChange={setOpen}
                clients={clients}
            />
        </AppLayout>
    );
}
