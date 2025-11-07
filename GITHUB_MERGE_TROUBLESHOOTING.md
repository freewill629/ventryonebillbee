# GitHub Merge Troubleshooting

If your latest changes are not showing up on GitHub after you merge locally, work through the checklist below.

## 1. Verify local branch status

Run the following commands from your repository root to confirm you are on the expected branch and that Git believes the merge is complete:

```bash
git status -sb
```

You should see `nothing to commit, working tree clean`. If you still have pending changes, commit or discard them before pushing.

## 2. Confirm the merge commit exists

List the most recent commits so you can confirm the merge you expect is present:

```bash
git log --oneline --decorate --graph -10
```

Make sure the merge commit (or the commits you expect to be uploaded) appear in this list.

## 3. Check your remote configuration

Ensure the repository is pointed at the correct GitHub remote:

```bash
git remote -v
```

If the URL is incorrect, update it with:

```bash
git remote set-url origin <correct-github-url>
```

## 4. Push the merge commit

Push the branch (typically `main` or `master`) to GitHub:

```bash
git push origin <branch-name>
```

Replace `<branch-name>` with the branch you merged locally (for example, `main`). If this is the first time you are pushing the branch, include `-u` so future pushes can omit the remote and branch names:

```bash
git push -u origin <branch-name>
```

## 5. Resolve non-fast-forward errors

If GitHub reports `non-fast-forward` or a similar error, it means the remote branch has new commits you do not have locally. Fetch and merge them before pushing again:

```bash
git fetch origin
git merge origin/<branch-name>
```

After resolving any conflicts and committing the merge, push again.

## 6. Force-push only when appropriate

If you intentionally rewrote history (for example with `git rebase`) and understand the impact on collaborators, you may need to force-push:

```bash
git push --force-with-lease origin <branch-name>
```

Use this only when you are certain it will not overwrite other people's work.

## 7. Verify on GitHub

After a successful push, refresh the GitHub page or check the commit list to confirm the merge appears. You may need to clear your browser cache or wait a few seconds for GitHub to update.

