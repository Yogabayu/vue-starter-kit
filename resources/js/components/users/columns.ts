// resources/js/components/users/columns.ts
import type { ColumnDef } from '@tanstack/vue-table'

export type UserRow = {
  id: number
  name: string
  email: string
  created_at: string
}

export const userColumns: ColumnDef<UserRow>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: ({ row }) => row.original.id,
    enableSorting: true,
  },
  {
    accessorKey: 'name',
    header: 'Name',
    cell: ({ row }) => row.original.name,
    enableSorting: true,
  },
  {
    accessorKey: 'email',
    header: 'Email',
    cell: ({ row }) => row.original.email,
    enableSorting: true,
  },
  {
    accessorKey: 'created_at',
    header: 'Created At',
    // tampilkan dd/mm/yyyy
    cell: ({ row }) => new Date(row.original.created_at).toLocaleDateString(),
    enableSorting: true,
  },
]
