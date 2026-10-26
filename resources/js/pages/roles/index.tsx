import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { useState } from 'react';
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/RoleController';
import AlertError from '@/components/alert-error';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
} from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermission } from '@/hooks/use-permission';

type RoleRow = {
    id: number;
    name: string;
    description: string | null;
    is_system: boolean;
    users_count: number;
};

type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginator<T> = {
    data: T[];
    links: PageLink[];
    last_page: number;
};

function linkLabel(label: string): string {
    return label
        .replaceAll('&laquo;', '«')
        .replaceAll('&raquo;', '»')
        .replaceAll(/<[^>]*>/g, '');
}

export default function Index({
    roles,
    filters,
}: {
    roles: Paginator<RoleRow>;
    filters: { search?: string };
}) {
    const { can } = usePermission();
    const errors = usePage().props.errors as Record<string, string>;
    const [pendingRole, setPendingRole] = useState<RoleRow | null>(null);
    const canUpdate = can('roles', 'can_update');
    const canDelete = can('roles', 'can_delete');

    return (
        <>
            <Head title="Roles" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title="Roles"
                        description="Permisos de cada rol sobre los módulos"
                    />
                    {can('roles', 'can_create') && (
                        <Button asChild>
                            <Link href={create.url()}>Crear rol</Link>
                        </Button>
                    )}
                </div>

                {errors.error && (
                    <AlertError
                        title="No se pudo completar la operación"
                        errors={[errors.error]}
                    />
                )}

                <Form
                    {...index.form()}
                    options={{ preserveState: true }}
                    className="flex max-w-md gap-2"
                >
                    {() => (
                        <>
                            <Input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Buscar por nombre"
                            />
                            <Button type="submit" variant="secondary">
                                Buscar
                            </Button>
                        </>
                    )}
                </Form>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Descripción</TableHead>
                                <TableHead>Usuarios</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead className="w-12">
                                    <span className="sr-only">Acciones</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5}>
                                        No hay roles.
                                    </TableCell>
                                </TableRow>
                            )}
                            {roles.data.map((role) => {
                                const showEdit = canUpdate && !role.is_system;
                                const showDelete = canDelete && !role.is_system;

                                return (
                                    <TableRow key={role.id}>
                                        <TableCell>{role.name}</TableCell>
                                        <TableCell>
                                            {role.description || '—'}
                                        </TableCell>
                                        <TableCell>{role.users_count}</TableCell>
                                        <TableCell>
                                            {role.is_system && (
                                                <Badge variant="secondary">
                                                    Sistema
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {(showEdit || showDelete) && (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger
                                                        asChild
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <MoreHorizontal />
                                                            <span className="sr-only">
                                                                Acciones
                                                            </span>
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        {showEdit && (
                                                            <DropdownMenuItem
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={edit.url(
                                                                        role.id,
                                                                    )}
                                                                >
                                                                    Editar
                                                                </Link>
                                                            </DropdownMenuItem>
                                                        )}
                                                        {showDelete && (
                                                            <DropdownMenuItem
                                                                onSelect={() =>
                                                                    setPendingRole(
                                                                        role,
                                                                    )
                                                                }
                                                            >
                                                                Eliminar
                                                            </DropdownMenuItem>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>

                {roles.last_page > 1 && (
                    <Pagination>
                        <PaginationContent>
                            {roles.links.map((link, linkIndex) => (
                                <PaginationItem
                                    key={`${link.label}-${linkIndex}`}
                                >
                                    <PaginationLink
                                        href={link.url ?? undefined}
                                        isActive={link.active}
                                        size="default"
                                        className={
                                            link.url
                                                ? undefined
                                                : 'pointer-events-none opacity-50'
                                        }
                                        onClick={(event) => {
                                            event.preventDefault();

                                            if (link.url) {
                                                router.visit(link.url, {
                                                    preserveScroll: true,
                                                });
                                            }
                                        }}
                                    >
                                        {linkLabel(link.label)}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                        </PaginationContent>
                    </Pagination>
                )}
            </div>

            <Dialog
                open={pendingRole !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingRole(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar rol</DialogTitle>
                        <DialogDescription>
                            {pendingRole
                                ? `Se eliminará el rol ${pendingRole.name}.`
                                : ''}
                        </DialogDescription>
                    </DialogHeader>
                    {pendingRole && (
                        <Form {...destroy.form(pendingRole.id)}>
                            {({ processing }) => (
                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => setPendingRole(null)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        Eliminar
                                    </Button>
                                </DialogFooter>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: index.url(),
        },
    ],
};
