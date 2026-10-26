import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { useState } from 'react';
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/UserController';
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

type RoleName = {
    id: number;
    name: string;
    is_system: boolean;
};

type UserRow = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: RoleName[];
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
    users,
    filters,
}: {
    users: Paginator<UserRow>;
    filters: { search?: string };
}) {
    const { can } = usePermission();
    const page = usePage<{
        auth: { user: { id: number } | null };
        errors: Record<string, string>;
    }>();
    const currentUserId = page.props.auth.user?.id;
    const errors = page.props.errors ?? {};
    const [pendingUser, setPendingUser] = useState<UserRow | null>(null);
    const canUpdate = can('users', 'can_update');
    const canDelete = can('users', 'can_delete');

    return (
        <>
            <Head title="Usuarios" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title="Usuarios"
                        description="Cuentas de administración y sus roles"
                    />
                    {can('users', 'can_create') && (
                        <Button asChild>
                            <Link href={create.url()}>Crear usuario</Link>
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
                                placeholder="Buscar por nombre o correo"
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
                                <TableHead>Correo</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="w-12">
                                    <span className="sr-only">Acciones</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5}>
                                        No hay usuarios.
                                    </TableCell>
                                </TableRow>
                            )}
                            {users.data.map((user) => {
                                const locked =
                                    user.id === currentUserId ||
                                    user.roles.some((role) => role.is_system);
                                const showEdit = canUpdate;
                                const showDeactivate =
                                    canDelete && !locked && user.is_active;

                                return (
                                    <TableRow key={user.id}>
                                        <TableCell>{user.name}</TableCell>
                                        <TableCell>{user.email}</TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles.map((role) => (
                                                    <Badge
                                                        key={role.id}
                                                        variant="secondary"
                                                    >
                                                        {role.name}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    user.is_active
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {user.is_active
                                                    ? 'Activo'
                                                    : 'Inactivo'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {(showEdit || showDeactivate) && (
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
                                                                        user.id,
                                                                    )}
                                                                >
                                                                    Editar
                                                                </Link>
                                                            </DropdownMenuItem>
                                                        )}
                                                        {showDeactivate && (
                                                            <DropdownMenuItem
                                                                onSelect={() =>
                                                                    setPendingUser(
                                                                        user,
                                                                    )
                                                                }
                                                            >
                                                                Desactivar
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

                {users.last_page > 1 && (
                    <Pagination>
                        <PaginationContent>
                            {users.links.map((link, linkIndex) => (
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
                open={pendingUser !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingUser(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Desactivar usuario</DialogTitle>
                        <DialogDescription>
                            {pendingUser
                                ? `${pendingUser.name} no podrá iniciar sesión.`
                                : ''}
                        </DialogDescription>
                    </DialogHeader>
                    {pendingUser && (
                        <Form {...destroy.form(pendingUser.id)}>
                            {({ processing }) => (
                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => setPendingUser(null)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        Desactivar
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
            title: 'Usuarios',
            href: index.url(),
        },
    ],
};
