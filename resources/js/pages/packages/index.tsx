import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';

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
import { formatMoney } from '@/lib/format';
import * as packages from '@/routes/packages';

type PackageRow = {
    id: number;
    name: string;
    type: string;
    type_label: string;
    amount: string;
    sessions_count: number | null;
    billing_interval: string | null;
    is_active: boolean;
};

const emptyPackage = {
    name: '',
    type: 'session',
    amount: '',
    sessions_count: '',
    billing_interval: '',
    is_active: true,
};

function PackageDialog({
    open,
    onOpenChange,
    pkg,
}: {
    open: boolean;
    onOpenChange: (value: boolean) => void;
    pkg: PackageRow | null;
}) {
    const form = useForm<typeof emptyPackage>({ ...emptyPackage });

    useEffect(() => {
        if (open) {
            form.setDefaults({
                name: pkg?.name ?? '',
                type: pkg?.type ?? 'session',
                amount: pkg?.amount ?? '',
                sessions_count: pkg?.sessions_count
                    ? String(pkg.sessions_count)
                    : '',
                billing_interval: pkg?.billing_interval ?? '',
                is_active: pkg?.is_active ?? true,
            });
            form.reset();
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, pkg]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };
        if (pkg) {
            form.put(packages.update(pkg.id).url, options);
        } else {
            form.post(packages.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {pkg ? 'Edit package' : 'New package'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="type">Type</Label>
                            <Select
                                id="type"
                                value={form.data.type}
                                onChange={(e) =>
                                    form.setData('type', e.target.value)
                                }
                            >
                                <option value="session">Single session</option>
                                <option value="package">Session package</option>
                                <option value="monthly">Monthly plan</option>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="amount">Amount</Label>
                            <Input
                                id="amount"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.amount}
                                onChange={(e) =>
                                    form.setData('amount', e.target.value)
                                }
                            />
                            <InputError message={form.errors.amount} />
                        </div>
                    </div>
                    {form.data.type === 'package' && (
                        <div className="space-y-2">
                            <Label htmlFor="sessions_count">
                                Sessions included
                            </Label>
                            <Input
                                id="sessions_count"
                                type="number"
                                min="1"
                                value={form.data.sessions_count}
                                onChange={(e) =>
                                    form.setData(
                                        'sessions_count',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.sessions_count} />
                        </div>
                    )}
                    {form.data.type === 'monthly' && (
                        <div className="space-y-2">
                            <Label htmlFor="billing_interval">
                                Billing interval
                            </Label>
                            <Select
                                id="billing_interval"
                                value={form.data.billing_interval}
                                onChange={(e) =>
                                    form.setData(
                                        'billing_interval',
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="">Choose…</option>
                                <option value="week">Weekly</option>
                                <option value="month">Monthly</option>
                                <option value="quarter">Quarterly</option>
                                <option value="year">Yearly</option>
                            </Select>
                            <InputError
                                message={form.errors.billing_interval}
                            />
                        </div>
                    )}
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(e) =>
                                form.setData('is_active', e.target.checked)
                            }
                        />
                        Active
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
                            {pkg ? 'Save' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function PackagesIndex({
    packages: rows,
    currency,
}: {
    packages: PackageRow[];
    currency: string;
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<PackageRow | null>(null);

    return (
        <AppLayout
            title="Packages"
            actions={
                <Button
                    size="sm"
                    onClick={() => {
                        setEditing(null);
                        setOpen(true);
                    }}
                >
                    <Plus className="size-4" /> New package
                </Button>
            }
        >
            <Head title="Packages" />

            <Card>
                <CardContent className="p-0">
                    {rows.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            Create reusable packages so invoicing is one click.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Details</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.map((pkg) => (
                                    <TableRow key={pkg.id}>
                                        <TableCell className="font-medium">
                                            {pkg.name}
                                        </TableCell>
                                        <TableCell>{pkg.type_label}</TableCell>
                                        <TableCell>
                                            {formatMoney(pkg.amount, currency)}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {pkg.sessions_count
                                                ? `${pkg.sessions_count} sessions`
                                                : pkg.billing_interval
                                                  ? `per ${pkg.billing_interval}`
                                                  : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    pkg.is_active
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {pkg.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => {
                                                        setEditing(pkg);
                                                        setOpen(true);
                                                    }}
                                                >
                                                    <Pencil className="size-4" />
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
                                                    title="Delete package?"
                                                    description={`${pkg.name} will be removed.`}
                                                    confirmLabel="Delete"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            packages.destroy(
                                                                pkg.id,
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

            <PackageDialog open={open} onOpenChange={setOpen} pkg={editing} />
        </AppLayout>
    );
}
