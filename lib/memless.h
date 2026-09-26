#define FFI_SCOPE "memless"

typedef uint64_t MemlessHandle;
typedef uint64_t MemlessResult;

/* 0 = Ok, 1 = Refused, 2 = InvalidArgument, 3 = Internal */
typedef int32_t MemlessStatus;

/* 0 = Absent, 1 = Text, 2 = Integer, 3 = Decimal, 4 = Boolean */
typedef int32_t MemlessKind;

/* ABI version 5. */
uint32_t memless_abi_version(void);

/*
 * Loads an instance on `path`.
 * When `out_message` is non-null it is always written: NULL on Ok, otherwise an
 * owned message the caller must release with `memless_free_string`. On Ok,
 * `*out_handle` receives a handle >= 1. `out_handle` and `out_message` may be
 * null; a null pointer is never written through.
 */
MemlessStatus memless_load(const char *path, MemlessHandle *out_handle, char **out_message);

/*
 * Runs `sql` against the instance behind `handle`.
 * When `out_message` is non-null it is always written: NULL on Ok, otherwise an
 * owned message the caller must release with `memless_free_string`. On Ok,
 * `*out_result` receives a result handle >= 1 the caller must release with
 * `memless_result_release`. A null `sql` or `out_result`, or an unknown handle,
 * yields InvalidArgument. `out_message` may be null; a null pointer is never
 * written through.
 *
 * The result is an independent copy: it stays valid after the instance is
 * released. Borrowed pointers (see below) are NOT synchronised with a
 * concurrent memless_result_release of the same result; the caller must not
 * release a result while it still holds a pointer into it.
 *
 * While a transaction is open on the handle, the query sees the transaction's
 * not-yet-committed writes (read-your-writes); otherwise it sees the committed
 * state. A non-SELECT sql yields Refused and never touches the transaction.
 */
MemlessStatus memless_query(MemlessHandle handle, const char *sql, MemlessResult *out_result, char **out_message);

/*
 * Runs a write `sql` (INSERT/UPDATE/DELETE) against the instance behind `handle`.
 * When `out_message` is non-null it is always written: NULL on Ok, otherwise an
 * owned message the caller must release with `memless_free_string`. On Ok,
 * `*out_affected` receives the affected row count. A null `sql`, or an unknown
 * handle, yields InvalidArgument; a SELECT, a validation failure or a disk failure
 * yields Refused with a message. `out_affected` and `out_message` may be null; a
 * null pointer is never written through, so a null `out_affected` simply drops the
 * count while the write still happens. An accepted write that changes the state
 * rewrites the file by substitution before the count is returned; a write that
 * changes nothing does not touch the disk. Like memless_query, an execute holds a
 * process-wide lock across the file rewrite (fsync + rename).
 *
 * BEGIN, COMMIT and ROLLBACK are also accepted (out_affected = 0). BEGIN opens a
 * transaction; writes then apply to a working state the file does not yet reflect,
 * and COMMIT rewrites the file once (fsync + rename) when the working state
 * differs from the committed one, and does not touch the disk otherwise, while
 * ROLLBACK discards the working state without touching the disk. A second BEGIN, or a COMMIT/ROLLBACK
 * with no open transaction, yields Refused; a write refused inside a transaction
 * leaves it open, while a COMMIT always closes it (a failed validation or disk
 * write leaves memory and the file at the before-state). Releasing the handle
 * mid-transaction discards the working state.
 */
MemlessStatus memless_execute(MemlessHandle handle, const char *sql, uint64_t *out_affected, char **out_message);

/*
 * Re-reads the file the instance was loaded on and replaces the in-memory
 * state with it, exactly as memless_load would build it. When `out_message`
 * is non-null it is always written: NULL on Ok, otherwise an owned message the
 * caller must release with memless_free_string. An unknown handle yields
 * InvalidArgument. Refused, with the state left intact and the instance still
 * usable: while a transaction is open on the handle, or when the file would be
 * refused by memless_load (same messages). On Ok nothing is written to disk;
 * results already obtained stay valid (they are independent copies). Like
 * memless_execute, a reload holds the process-wide lock for its duration.
 * `out_message` may be null; a null pointer is never written through.
 */
MemlessStatus memless_reload(MemlessHandle handle, char **out_message);

/* Number of columns / rows in the result; 0 for an unknown result. */
uint64_t memless_result_column_count(MemlessResult result);
uint64_t memless_result_row_count(MemlessResult result);

/* Column name at `index`, borrowed until memless_result_release; NULL out of range. */
const char *memless_result_column(MemlessResult result, uint64_t index);

/*
 * Reads the cell at (`row`, `column`). Returns its kind and, for a present
 * scalar, writes one payload: Text sets `*out_text` to a borrowed pointer valid
 * until memless_result_release; Integer/Decimal/Boolean set their out_* value.
 * Any out_* pointer may be null. Absent (also out of range) writes nothing.
 */
MemlessKind memless_result_cell(MemlessResult result, uint64_t row, uint64_t column,
                                int64_t *out_integer, double *out_decimal,
                                int32_t *out_boolean, const char **out_text);

/* Releases the result behind `result`; an unknown or zero result is ignored. */
void memless_result_release(MemlessResult result);

/* Releases the instance behind `handle`; an unknown or zero handle is ignored. */
void memless_release(MemlessHandle handle);

/* Frees a message returned through `out_message`; NULL is ignored. */
void memless_free_string(char *message);
