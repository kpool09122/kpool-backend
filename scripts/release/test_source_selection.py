import os
from pathlib import Path
import subprocess
import tempfile
import unittest

SCRIPT = Path(__file__).with_name('select-source.sh')


class SourceSelectionTests(unittest.TestCase):
    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.repository = Path(self.directory.name)
        self.git('init', '-q', '-b', 'main')
        self.git('config', 'user.name', 'Release Test')
        self.git('config', 'user.email', 'release-test@example.invalid')
        self.old = self.commit('approved-old')
        self.git('checkout', '-q', '-b', 'unmerged')
        self.unmerged = self.commit('unapproved')
        self.git('checkout', '-q', 'main')
        self.main = self.commit('approved-main')
        self.git('update-ref', 'refs/remotes/origin/main', self.main)

    def git(self, *arguments):
        return subprocess.check_output(['git', '-C', str(self.repository), *arguments], text=True,
                                       stderr=subprocess.DEVNULL).strip()

    def commit(self, content):
        (self.repository/'content.txt').write_text(content)
        self.git('add', 'content.txt')
        self.git('commit', '-q', '-m', content)
        return self.git('rev-parse', 'HEAD')

    def select(self, sha):
        return subprocess.run(['bash', str(SCRIPT), str(self.repository)],
                              env=dict(os.environ, SOURCE_SHA=sha), capture_output=True, text=True)

    def test_pinned_checkout_accepts_release_commit_outside_main(self):
        for sha in (self.main, self.old, self.unmerged):
            with self.subTest(sha=sha):
                self.git('checkout', '-q', '--detach', sha)
                self.assertEqual(self.select(sha).returncode, 0)
                self.assertEqual(self.git('rev-parse', 'HEAD'), sha)

    def test_wrong_missing_or_injected_sha_never_changes_checkout(self):
        for sha in (self.unmerged, 'f'*40, 'main', self.old[:12], self.old.upper(), '$(touch injected)',
                    self.git('rev-parse', 'HEAD:content.txt')):
            with self.subTest(sha=sha):
                self.assertNotEqual(self.select(sha).returncode, 0)
                self.assertEqual(self.git('rev-parse', 'HEAD'), self.main)
                self.assertFalse((self.repository/'injected').exists())
