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

    def test_main_and_historical_commit_select_exact_detached_source(self):
        for sha, content in [(self.main, 'approved-main'), (self.old, 'approved-old')]:
            with self.subTest(sha=sha):
                self.git('checkout', '-q', 'main')
                self.assertEqual(self.select(sha).returncode, 0)
                self.assertEqual(self.git('rev-parse', 'HEAD'), sha)
                self.assertEqual((self.repository/'content.txt').read_text(), content)
                self.assertEqual(self.git('rev-parse', '--abbrev-ref', 'HEAD'), 'HEAD')

    def test_invalid_missing_and_unmerged_commits_never_change_source(self):
        for sha in [self.unmerged, 'f'*40, 'main', self.old[:12], self.old.upper(), '$(touch injected)']:
            with self.subTest(sha=sha):
                self.assertNotEqual(self.select(sha).returncode, 0)
                self.assertEqual(self.git('rev-parse', 'HEAD'), self.main)
                self.assertEqual((self.repository/'content.txt').read_text(), 'approved-main')
                self.assertFalse((self.repository/'injected').exists())

    def test_non_commit_object_is_rejected_before_switching(self):
        blob = self.git('rev-parse', 'HEAD:content.txt')
        self.assertNotEqual(self.select(blob).returncode, 0)
        self.assertEqual(self.git('rev-parse', 'HEAD'), self.main)

    def test_wrong_starting_revision_is_rejected(self):
        self.git('checkout', '-q', '--detach', self.unmerged)
        self.assertNotEqual(self.select(self.old).returncode, 0)
        self.assertEqual(self.git('rev-parse', 'HEAD'), self.unmerged)

    def test_incomplete_history_is_rejected(self):
        (self.repository/'.git/shallow').write_text(self.main+'\n')
        self.assertNotEqual(self.select(self.main).returncode, 0)
        self.assertEqual(self.git('rev-parse', 'HEAD'), self.main)
