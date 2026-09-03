import { Head, router, useForm } from '@inertiajs/react';
import { Mail, Pencil, Trash2, Upload, UserPlus } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/format';
import * as clients from '@/routes/clients';

type ClientRow = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    default_rate: string | null;
    payment_preference: string | null;
    notes: string | null;
    status: string;
    invited: boolean;
    sessions_count: number;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    clients: Paginated<ClientRow>;
    filters: { search: string; status: string };
    currency: string;
};

const emptyClient = {
    name: '',
    email: '',
    phone: '',
    default_rate: '',
    payment_preference: '',
    notes: '',
    status: 'active',
};

function ClientDialog({
    open,
    onOpenChange,
    client,
}: {
    open: boolean;
    onOpenChange: (value: boolean) => void;
    client: ClientRow | null;
}) {
    const form = useForm<typeof emptyClient>({ ...emptyClient });

    useEffect(() => {
        if (open) {
            form.setDefaults({
                name: client?.name ?? '',
                email: client?.email ?? '',
                phone: client?.phone ?? '',
                default_rate: client?.default_rate ?? '',
                payment_preference: client?.payment_preference ?? '',
                notes: client?.notes ?? '',
                status: client?.status ?? 'active',
            });
            form.reset();
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, client]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (client) {
            form.put(clients.update(client.id).url, options);
        } else {
            form.post(clients.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {client ? 'Edit client' : 'Add client'}
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
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                            />
                            <InputError message={form.errors.email} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(e) =>
                                    form.setData('phone', e.target.value)
                                }
                            />
                            <InputError message={form.errors.phone} />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="default_rate">
                                Default session rate
                            </Label>
                            <Input
                                id="default_rate"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.default_rate}
                                onChange={(e) =>
                                    form.setData('default_rate', e.target.value)
                                }
                            />
                            <InputError message={form.errors.default_rate} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="payment_preference">
                                Payment preference
                            </Label>
                            <Select
                                id="payment_preference"
                                value={form.data.payment_preference}
                                onChange={(e) =>
                                    form.setData(
                                        'payment_preference',
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="">No preference</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                                <option value="cash">Cash</option>
                            </Select>
                            <InputError
                                message={form.errors.payment_preference}
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="status">Status</Label>
                        <Select
                            id="status"
                            value={form.data.status}
                            onChange={(e) =>
                                form.setData('status', e.target.value)
                            }
                        >
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                        />
                        <InputError message={form.errors.notes} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {client ? 'Save changes' : 'Add client'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ImportDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (value: boolean) => void;
}) {
    const form = useForm<{ file: File | null }>({ file: null });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(clients.importMethod().url, {
            forceFormData: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Import clients from CSV</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <p className="text-muted-foreground text-sm">
                        The file needs a header row with columns{' '}
                        <code>name</code>, <code>email</code>,{' '}
                        <code>phone</code>, <code>rate</code>.
                    </p>
                    <Input
                        type="file"
                        accept=".csv,text/csv"
                        onChange={(e) =>
                            form.setData('file', e.target.files?.[0] ?? null)
                        }
                    />
                    <InputError message={form.errors.file} />
                    <DialogFooter>
                        <Button
                            type="submit"
                            disabled={form.processing || !form.data.file}
                        >
                            Import
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function ClientsIndex({
    clients: page,
    filters,
    currency,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const [editing, setEditing] = useState<ClientRow | null>(null);

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                clients.index().url,
                { search, status },
                { preserveState: true, replace: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, status]);

    return (
        <AppLayout
            title="Clients"
            actions={
                <div className="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setImportOpen(true)}
                    >
                        <Upload className="size-4" /> Import
                    </Button>
                    <Button
                        size="sm"
                        onClick={() => {
                            setEditing(null);
                            setDialogOpen(true);
                        }}
                    >
                        <UserPlus className="size-4" /> Add client
                    </Button>
                </div>
            }
        >
            <Head title="Clients" />

            <div className="mb-4 flex flex-wrap gap-3">
                <Input
                    placeholder="Search name, email or phone"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="max-w-xs"
                />
                <Select
                    value={status}
                    onChange={(e) => setStatus(e.target.value)}
                    className="max-w-[10rem]"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </Select>
            </div>

            <Card>
                <CardContent className="p-0">
                    {page.data.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            No clients yet. Add your first one to start logging
                            sessions.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Contact</TableHead>
                                    <TableHead>Rate</TableHead>
                                    <TableHead>Sessions</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {page.data.map((client) => (
                                    <TableRow key={client.id}>
                                        <TableCell className="font-medium">
                                            {client.name}
                                            {client.invited && (
                                                <Badge
                                                    variant="success"
                                                    className="ml-2"
                                                >
                                                    Portal
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {client.email ?? '—'}
                                            {client.phone && (
                                                <div className="text-xs">
                                                    {client.phone}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {client.default_rate
                                                ? formatMoney(
                                                      client.default_rate,
                                                      currency,
                                                  )
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            {client.sessions_count}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    client.status === 'active'
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {client.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-1">
                                                {!client.invited &&
                                                    client.email && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            title="Invite to portal"
                                                            onClick={() =>
                                                                router.post(
                                                                    clients.invite(
                                                                        client.id,
                                                                    ).url,
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <Mail className="size-4" />
                                                        </Button>
                                                    )}
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => {
                                                        setEditing(client);
                                                        setDialogOpen(true);
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
                                                    title="Remove client?"
                                                    description={`This deletes ${client.name} and their logged sessions.`}
                                                    confirmLabel="Remove"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            clients.destroy(
                                                                client.id,
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

            {page.links.length > 3 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {page.links.map((link, index) => (
                        <Button
                            key={index}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            disabled={!link.url}
                            onClick={() =>
                                link.url &&
                                router.get(
                                    link.url,
                                    {},
                                    {
                                        preserveState: true,
                                        preserveScroll: true,
                                    },
                                )
                            }
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}

            <ClientDialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                client={editing}
            />
            <ImportDialog open={importOpen} onOpenChange={setImportOpen} />
        </AppLayout>
    );
}
