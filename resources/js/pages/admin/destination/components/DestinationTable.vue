<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { ref, watch } from 'vue'

// Props dari parent
const props = defineProps({
    rows: { type: Array, required: true },
    total: { type: Number, default: 0 },
    page: { type: Number, default: 1 },
    perPage: { type: Number, default: 10 },
    lastPage: { type: Number, default: 1 },
    sortKey: { type: String, default: 'id' },
    sortDir: { type: String, default: 'asc' },
    loading: { type: Boolean, default: false },
})

// Events biar parent bisa dengar perubahan
const emit = defineEmits(['update:page', 'update:perPage', 'update:sort', 'edit', 'delete'])

const page = ref(props.page)
const perPage = ref(props.perPage)
const sortKey = ref(props.sortKey)
const sortDir = ref<'asc' | 'desc'>(props.sortDir as 'asc' | 'desc')

function setSort(key: string) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortDir.value = 'asc'
    }
    emit('update:sort', { key: sortKey.value, dir: sortDir.value })
}

watch(page, (val) => emit('update:page', val))
watch(perPage, (val) => emit('update:perPage', val))
</script>

<template>
    <div class="w-full overflow-x-auto">
        <Table>
            <TableCaption v-if="total">Total: {{ total }}</TableCaption>

            <!-- Header -->
            <TableHeader>
                <TableRow>
                    <TableHead
                        class="cursor-pointer select-none"
                        @click="setSort('id')"
                    >
                        ID
                        <span v-if="sortKey === 'id'">({{ sortDir }})</span>
                    </TableHead>

                    <TableHead
                        class="cursor-pointer select-none"
                        @click="setSort('name')"
                    >
                        Name
                        <span v-if="sortKey === 'name'">({{ sortDir }})</span>
                    </TableHead>

                    <TableHead
                        class="cursor-pointer select-none"
                        @click="setSort('details.address')"
                    >
                        Address
                        <span v-if="sortKey === 'address'">({{ sortDir }})</span>
                    </TableHead>

                    <TableHead
                        class="cursor-pointer select-none"
                        @click="setSort('created_at')"
                    >
                        Created At
                        <span v-if="sortKey === 'created_at'">({{ sortDir }})</span>
                    </TableHead>

                    <TableHead class="text-center">Action</TableHead>
                </TableRow>
            </TableHeader>

            <!-- Body -->
            <TableBody>
                <TableRow v-if="loading">
                    <TableCell colspan="5" class="text-center text-muted-foreground">
                        Loading...
                    </TableCell>
                </TableRow>

                <TableRow
                    v-for="d in rows"
                    :key="d.id"
                    v-else
                >
                    <TableCell>
                        {{ (page - 1) * perPage + rows.indexOf(d) + 1 }}
                    </TableCell>
                    <TableCell>{{ d.name }}</TableCell>
                    <TableCell>{{ d.detail.address }}</TableCell>
                    <TableCell>
                        <!-- {{ d }} -->
                        {{ new Date(d.created_at).toLocaleDateString() }}
                    </TableCell>
                    <TableCell class="text-right">
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                >Details</Button
                            >
                            <Button
                                size="sm"
                                variant="outline"
                                >Images</Button
                            >
                            <Button
                                size="sm"
                                variant="outline"
                                @click="$emit('edit', d)"
                                >Edit</Button
                            >
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="$emit('delete', d.id)"
                                >Delete</Button
                            >
                        </div>
                    </TableCell>
                </TableRow>

                <TableRow v-if="!loading && rows.length === 0">
                    <TableCell colspan="4" class="text-center text-muted-foreground">
                        No data
                    </TableCell>
                </TableRow>
            </TableBody>

            <!-- Footer -->
            <TableFooter>
                <TableRow>
                    <TableCell colspan="5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm text-muted-foreground">
                                Page {{ page }} / {{ lastPage }}
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    :disabled="page <= 1"
                                    @click="page--"
                                    >Prev</Button
                                >
                                <Button
                                    variant="outline"
                                    :disabled="page >= lastPage"
                                    @click="page++"
                                    >Next</Button
                                >
                                <select
                                    v-model.number="perPage"
                                    class="rounded-md border bg-background px-2 py-1 text-foreground dark:bg-muted dark:text-foreground"
                                >
                                    <option :value="5">5</option>
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                </select>
                            </div>
                        </div>
                    </TableCell>
                </TableRow>
            </TableFooter>
        </Table>
    </div>
</template>
