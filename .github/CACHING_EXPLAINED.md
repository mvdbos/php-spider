# How Composer Caching Works with act

## The Problems (and Solutions)

### Problem 1: GitHub's Cloud Cache Doesn't Work Locally
GitHub Actions uses a cloud-based cache (`actions/cache@v5`), which isn't available when running act locally. Warning:
```
🚧 ::warning::Failed to save: reserveCache failed: socket hang up
```
**Solution**: Use Docker volume mounts for local caching.

### Problem 2: Different PHP Versions Have Incompatible Vendor Directories
If you share the vendor directory between PHP 8.2 and PHP 8.3 runs, you get:
```
Your lock file does not contain a compatible set of packages. Please run composer update.
```
**Why?** Different PHP versions resolve dependencies differently.

**Solution**: Cache only Composer **packages**, not vendor directories. Vendor is installed fresh for each PHP version.

### Problem 3: Hardcoded User Paths Aren't Portable
The old setup used `~/.cache/`, which meant the cache wasn't portable across machines.

**Solution**: Store cache in the project directory (`.cache/`) and gitignore it.

## The Solution: Project-Local Composer Cache

We cache only the Composer **packages**, not the vendor directory:

```
Project Directory (.gitignored)       Docker Container
────────────────────────────          ────────────────
.cache/composer/        ═════════>   /root/.composer/cache
  (packages - reused)                  (Composer downloads here)

vendor/                 (NOT MOUNTED)
  (fresh per run)                      (installed per PHP version)
```

## Configuration

In [.actrc](.actrc):
```
--container-options=-v=/workspace/.cache/composer:/root/.composer/cache
```

This mounts only the Composer package cache. The vendor directory is installed fresh each run.

Benefits:
1. ✅ **Reuses downloaded packages** across all PHP versions (fast!)
2. ✅ **Fresh vendor per PHP version** (avoids lock file conflicts)
3. ✅ **All stored in project** (portable, no hardcoded user paths)
4. ✅ **Gitignored** (doesn't clutter your repo)

## Performance Impact

### First Run
```bash
./bin/act --matrix php-versions:8.3
```
- Downloads Docker image (~2GB): **~5 min**
- Downloads Composer packages: **~5 min**
- Installs vendor/: **~2 min**
- Runs tests: **~2 min**
**Total: ~15 minutes**

### Second Run with Same PHP (Cached!)
```bash
./bin/act --matrix php-versions:8.3
```
- Uses cached Docker image: **instant**
- Reuses Composer packages: **~30 sec**
- Installs fresh vendor/: **~2 min**
- Runs tests: **~2 min**
**Total: ~5 minutes** 🚀

### Run with Different PHP Version
```bash
./bin/act --matrix php-versions:8.2
```
- Uses cached Docker image: **instant**
- Reuses Composer packages: **~30 sec**
- Installs vendor for PHP 8.2: **~2 min**
- Runs tests: **~2 min**
**Total: ~5 minutes** (no conflicts!)

## Cache Locations

All cache is stored in the project directory and gitignored:

```bash
# View cache sizes
du -sh .cache/composer/
du -sh .cache/act/

# Clear Composer package cache (forces re-download)
rm -rf .cache/composer/

# Clear all cache
rm -rf .cache/
```

## Why This is Better

✅ **Portable** - No hardcoded user paths  
✅ **Multi-project friendly** - Cache is local to project  
✅ **PHP version safe** - No conflicts between versions  
✅ **Gitignored** - Doesn't pollute your repository  
✅ **Fast** - Reuses packages across runs
